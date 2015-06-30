<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Exception\InvalidConfigurationException;

class ObjectMapper extends AbstractMapper
{
    /**
     * @param EventDispatcherInterface $dispatcher
     * @param $mappingConfig
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
     * Set related fields values
     *
     * @param string $alias
     * @param array  $objectData
     * @param array  $fieldConfig
     * @param object $object
     * @param string $parentFieldName
     * @param bool   $isArray
     *
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function processRelatedFields(
        $alias,
        $objectData,
        $fieldConfig,
        $object,
        $parentFieldName = '',
        $isArray = false
    ) {
        $fieldValue = $this->getFieldValue($object, $fieldConfig['name']);
        if (null === $fieldValue) {
            // return $objectData unchanged
            return $objectData;
        }

        $parentFieldName .= $parentFieldName ? ucfirst($fieldConfig['name']) : $fieldConfig['name'];
        if (isset($fieldConfig['relation_type']) && !empty($fieldConfig['relation_fields'])) {
            // many-to-many and one-to-many relations are expected to be joined on a collection
            $isCollection = (
                $fieldConfig['relation_type'] == Indexer::RELATION_MANY_TO_MANY
                || $fieldConfig['relation_type'] == Indexer::RELATION_ONE_TO_MANY
            );

            if (!$isCollection) {
                $fieldValue = [$fieldValue];
            } elseif (!is_array($fieldValue) && !$fieldValue instanceof \Traversable) {
                throw new InvalidConfigurationException(
                    sprintf(
                        'The field "%s" specified as "%s" relation for entity "%s" is not a collection.',
                        $fieldConfig['name'],
                        $fieldConfig['relation_type'],
                        $alias
                    )
                );
            }
            foreach ($fieldValue as $relationObject) {
                foreach ($fieldConfig['relation_fields'] as $relationObjectField) {
                    // recursive processing of related fields
                    $objectData = $this->processRelatedFields(
                        $alias,
                        $objectData,
                        $relationObjectField,
                        $relationObject,
                        $parentFieldName,
                        $isCollection || $isArray // if there was at least one *-to-many relation in chain
                    );
                }
            }
        } else {
            if (empty($fieldConfig['target_fields'])) {
                $fieldConfig['target_fields'] = [$parentFieldName];
            }
            $objectData = $this->setDataValue($alias, $objectData, $fieldConfig, $fieldValue, $isArray);
        }

        return $objectData;
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
        $objectData = [];
        $objectClass = ClassUtils::getRealClass($object);
        if (is_object($object) && $this->mappingProvider->isFieldsMappingExists($objectClass)) {
            $alias = $this->getEntityMapParameter($objectClass, 'alias', $objectClass);
            foreach ($this->getEntityMapParameter($objectClass, 'fields', array()) as $field) {
                $objectData = $this->processRelatedFields($alias, $objectData, $field, $object);
            }

            /**
             *  Dispatch oro_search.prepare_entity_map event
             */
            $event = new PrepareEntityMapEvent($object, $objectClass, $objectData);
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
        $config = $this->getEntityConfig($entityName);
        if ($config['mode'] !== Mode::NORMAL) {
            return array_filter(
                $this->getEntities(),
                function ($className) use ($entityName) {
                    return is_subclass_of($className, $entityName);
                }
            );
        }

        return false;
    }
}
