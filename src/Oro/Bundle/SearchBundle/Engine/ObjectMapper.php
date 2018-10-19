<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Preparing storable index data from entities.
 *
 * @package Oro\Bundle\SearchBundle\Engine
 */
class ObjectMapper extends AbstractMapper
{
    /**
     * @param EventDispatcherInterface $dispatcher
     * @param                          $mappingConfig
     */
    public function __construct(EventDispatcherInterface $dispatcher, $mappingConfig)
    {
        $this->dispatcher    = $dispatcher;
        $this->mappingConfig = $mappingConfig;
    }

    /**
     * @return array
     */
    public function getMappingConfig()
    {
        return $this->mappingProvider->getMappingConfig();
    }

    /**
     * Get array with entity aliases
     *
     * @return array
     *  key - entity class name
     *  value - entity search alias
     */
    public function getEntitiesListAliases()
    {
        return $this->mappingProvider->getEntitiesListAliases();
    }

    /**
     * Gets search aliases for entities
     *
     * @param string[] $classNames The list of entity FQCN
     *
     * @return array [entity class name => entity search alias, ...]
     *
     * @throws \InvalidArgumentException if some of requested entities is not registered in the search index
     *                                   or has no the search alias
     */
    public function getEntityAliases(array $classNames = [])
    {
        return $this->mappingProvider->getEntityAliases($classNames);
    }

    /**
     * Gets the search alias of a given entity
     *
     * @param string $className The FQCN of an entity
     *
     * @return string|null The search alias of the entity
     *                     or NULL if the entity is not registered in a search index or has no the search alias
     */
    public function getEntityAlias($className)
    {
        return $this->mappingProvider->getEntityAlias($className);
    }

    /**
     * Get search entities list
     *
     * @param null|string|string[] $modeFilter Filter entities by "mode"
     *
     * @return array
     */
    public function getEntities($modeFilter = null)
    {
        $entities = $this->mappingProvider->getEntityClasses();
        if (null == $modeFilter) {
            return $entities;
        }

        $self = $this;

        return array_filter(
            $entities,
            function ($entityName) use ($modeFilter, $self) {
                $mode = $self->getEntityModeConfig($entityName);

                return is_array($modeFilter) ? in_array($mode, $modeFilter, true) : $mode === $modeFilter;
            }
        );
    }

    /**
     * Map object data for index
     *
     * @param object $object
     *
     * @return array
     */
    public function mapObject($object)
    {
        $objectData  = [];
        $objectClass = ClassUtils::getRealClass($object);
        if (is_object($object) && $this->mappingProvider->hasFieldsMapping($objectClass)) {
            $alias = $this->getEntityMapParameter($objectClass, 'alias', $objectClass);
            foreach ($this->getEntityMapParameter($objectClass, 'fields', []) as $field) {
                $objectData = $this->processField($alias, $objectData, $field, $object);
            }

            /**
             *  Dispatch oro_search.prepare_entity_map event
             */
            $event = new PrepareEntityMapEvent(
                $object,
                $objectClass,
                $objectData,
                $this->getEntityConfig($objectClass)
            );
            $this->dispatcher->dispatch(PrepareEntityMapEvent::EVENT_NAME, $event);
            $objectData = $event->getData();
        }

        return $objectData;
    }

    /**
     * Find descendants for class from list of known classes
     *
     * @param string $entityName
     *
     * @return array|false Returns descendants FQCN array or FALSE if "mode" is equals to "normal"
     */
    public function getRegisteredDescendants($entityName)
    {
        $mode = $this->getEntityModeConfig($entityName);
        if ($mode !== Mode::NORMAL) {
            return array_filter(
                $this->getEntities(),
                function ($className) use ($entityName) {
                    return is_subclass_of($className, $entityName);
                }
            );
        }

        return false;
    }

    /**
     * Gathers additionally selected fields from the search index
     * into an output array.
     *
     * @param Query $query
     * @param array $resultItem
     * @return array
     */
    public function mapSelectedData(Query $query, $resultItem)
    {
        $dataFields = $query->getSelectDataFields();

        if (empty($dataFields)) {
            return [];
        }

        $result = [];

        foreach ($dataFields as $column => $dataField) {
            list($type, $columnName) = Criteria::explodeFieldTypeName($column);

            $value = '';

            if (isset($resultItem[$columnName])) {
                $value = $resultItem[$columnName];
            }

            if (isset($resultItem[$dataField])) {
                $value = $resultItem[$dataField];
            }

            if (is_array($value)) {
                $value = array_shift($value);
            }

            if (is_numeric($value)) {
                if ($type === Query::TYPE_INTEGER) {
                    $value = (int)$value;
                } elseif ($type === Query::TYPE_DECIMAL) {
                    $value = (float)$value;
                }
            }

            $result[$dataField] = $value;
        }

        return $result;
    }

    /**
     * Processes field mapping
     *
     * @param string $alias
     * @param array  $objectData
     * @param array  $fieldConfig
     * @param object $object
     * @param string $parentFieldName
     * @param bool   $isArray
     *
     * @return array
     */
    protected function processField(
        $alias,
        $objectData,
        $fieldConfig,
        $object,
        $parentFieldName = null,
        $isArray = false
    ) {
        $fieldValue = $this->getFieldValue($object, $fieldConfig['name']);
        if (null === $fieldValue) {
            // return $objectData unchanged
            return $objectData;
        }

        if (isset($fieldConfig['relation_type']) && !empty($fieldConfig['relation_fields'])) {
            foreach ($fieldConfig['relation_fields'] as $relationField) {
                $objectData = $this->processRelatedField(
                    $alias,
                    $objectData,
                    $relationField,
                    $fieldValue,
                    $fieldConfig['relation_type'],
                    $fieldConfig['name'],
                    $isArray
                );
            }
        } else {
            if (empty($fieldConfig['target_fields']) && $parentFieldName) {
                $fieldConfig['target_fields'] = [$parentFieldName];
            }
            $objectData = $this->setDataValue($alias, $objectData, $fieldConfig, $fieldValue, $isArray);
        }

        return $objectData;
    }

    /**
     * Processes related field mapping
     *
     * @param string $alias
     * @param array  $objectData
     * @param array  $fieldConfig
     * @param object $object
     * @param string $relationType
     * @param string $parentFieldName
     * @param bool   $isArray
     *
     * @return array
     *
     * @throws InvalidConfigurationException
     */
    protected function processRelatedField(
        $alias,
        $objectData,
        $fieldConfig,
        $object,
        $relationType,
        $parentFieldName,
        $isArray = false
    ) {
        // many-to-many and one-to-many relations are expected to be joined on a collection
        $isCollection =
            $relationType === Indexer::RELATION_MANY_TO_MANY
            || $relationType === Indexer::RELATION_ONE_TO_MANY;

        if (!$isCollection) {
            $object = [$object];
        } elseif (!is_array($object) && !$object instanceof \Traversable) {
            throw new InvalidConfigurationException(
                sprintf(
                    'The field "%s" specified as "%s" relation for entity "%s" is not a collection.',
                    $fieldConfig['name'],
                    $relationType,
                    $alias
                )
            );
        }
        foreach ($object as $relationObject) {
            $objectData = $this->processField(
                $alias,
                $objectData,
                $fieldConfig,
                $relationObject,
                $parentFieldName,
                $isCollection || $isArray // if there was at least one *-to-many relation in chain
            );
        }

        return $objectData;
    }

    /**
     * Keep HTML in text fields except all_text* fields
     *
     * {@inheritdoc}
     */
    protected function clearTextValue($fieldName, $value)
    {
        if (strpos($fieldName, Indexer::TEXT_ALL_DATA_FIELD) === 0) {
            $value = $this->htmlTagHelper->stripTags((string)$value);
            $value = $this->htmlTagHelper->stripLongWords($value);
        }

        return $value;
    }
}
