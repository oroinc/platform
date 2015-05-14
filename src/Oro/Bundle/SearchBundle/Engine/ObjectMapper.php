<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Oro\Bundle\SearchBundle\Event\BeforeMapObjectEvent;
use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;

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
        return $this->mappingConfig;
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
        $entities = [];

        foreach ($this->mappingConfig as $class => $mappingEntity) {
            $entities[$class] = isset($mappingEntity['alias']) ? $mappingEntity['alias'] : '';
        }

        return $entities;
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
        $entities = array_keys($this->mappingConfig);
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
        $objectData = [];

        /**
         *  dispatch oro_search.before_map_object event
         */
        $event = new BeforeMapObjectEvent($this->mappingConfig, $object);
        $this->dispatcher->dispatch(BeforeMapObjectEvent::EVENT_NAME, $event);
        $this->mappingConfig = $event->getMappingConfig();

        $objectClass = ClassUtils::getRealClass($object);
        if (is_object($object) && isset($this->mappingConfig[$objectClass])) {
            $alias = $this->getEntityMapParameter($objectClass, 'alias', $objectClass);
            foreach ($this->getEntityMapParameter($objectClass, 'fields', array()) as $field) {
                if (!isset($field['relation_type'])) {
                    $field['relation_type'] = 'none';
                }
                $value = $this->getFieldValue($object, $field['name']);
                if (null === $value) {
                    continue;
                }
                switch ($field['relation_type']) {
                    case Indexer::RELATION_ONE_TO_ONE:
                    case Indexer::RELATION_MANY_TO_ONE:
                        $objectData = $this->setRelatedFields(
                            $alias,
                            $objectData,
                            $field['relation_fields'],
                            $value,
                            $field['name']
                        );
                        break;
                    case Indexer::RELATION_MANY_TO_MANY:
                    case Indexer::RELATION_ONE_TO_MANY:
                        foreach ($value as $relationObject) {
                            $objectData = $this->setRelatedFields(
                                $alias,
                                $objectData,
                                $field['relation_fields'],
                                $relationObject,
                                $field['name'],
                                true
                            );
                        }
                        break;
                    default:
                        $objectData = $this->setDataValue($alias, $objectData, $field, $value);
                }
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
