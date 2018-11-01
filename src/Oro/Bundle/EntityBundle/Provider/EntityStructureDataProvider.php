<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides detailed information about entities
 * including options collected via "oro_entity.structure.options" event listeners.
 */
class EntityStructureDataProvider
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var EntityWithFieldsProvider */
    protected $entityWithFieldsProvider;

    /** @var EntityClassNameHelper */
    protected $classNameHelper;

    /** @var array */
    protected $entityPropertyMappings = [
        'name' => 'setClassName',
        'label' => 'setLabel',
        'plural_label' => 'setPluralLabel',
        'icon' => 'setIcon',
        'routes' => 'setRoutes',
    ];

    /** @var array */
    protected $fieldPropertyMappings = [
        'name' => 'setName',
        'type' => 'setType',
        'label' => 'setLabel',
        'relation_type' => 'setRelationType',
        'related_entity_name' => 'setRelatedEntityName',
    ];

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityWithFieldsProvider $entityWithFieldsProvider
     * @param EntityClassNameHelper    $classNameHelper
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityWithFieldsProvider $entityWithFieldsProvider,
        EntityClassNameHelper $classNameHelper
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->entityWithFieldsProvider = $entityWithFieldsProvider;
        $this->classNameHelper = $classNameHelper;
    }

    /**
     * @return EntityStructure[]
     */
    public function getEntities()
    {
        $entityStructures = $this->processEntities();

        $event = new EntityStructureOptionsEvent();
        $event->setData($entityStructures);

        return $this->eventDispatcher->dispatch(EntityStructureOptionsEvent::EVENT_NAME, $event)->getData();
    }

    /**
     * @param string $entityName The class name or url-safe class name of the entity
     *
     * @return EntityStructure
     */
    public function getEntity($entityName)
    {
        $entityClass = $this->classNameHelper->resolveEntityClass($entityName);
        $entity = $this->entityWithFieldsProvider
            ->getFieldsForEntity($entityClass, true, true, true, false, true, true);
        $model = $this->processEntity($entity);

        $event = new EntityStructureOptionsEvent();
        $event->setData([$model]);
        $this->eventDispatcher->dispatch(EntityStructureOptionsEvent::EVENT_NAME, $event);

        return $model;
    }

    /**
     * @return EntityStructure[]
     */
    protected function processEntities()
    {
        $result = [];

        $data = $this->entityWithFieldsProvider->getFields(true, true, true, false, true, true);
        foreach ($data as $item) {
            $model = $this->processEntity($item);
            $result[$model->getClassName()] = $model;
        }

        return $result;
    }

    /**
     * @param array $entity
     *
     * @return EntityStructure
     */
    protected function processEntity(array $entity)
    {
        $model = new EntityStructure();
        foreach ($this->entityPropertyMappings as $name => $method) {
            if (isset($entity[$name])) {
                $model->{$method}($entity[$name]);
            }
        }

        $entityClass = $model->getClassName();
        $model->setId($this->classNameHelper->getUrlSafeClassName($entityClass));
        if (!empty($entity['fields'])) {
            $this->processFields($model, $entity['fields']);
        }

        return $model;
    }

    /**
     * @param EntityStructure $structure
     * @param array           $fields
     */
    protected function processFields(EntityStructure $structure, array $fields)
    {
        foreach ($fields as $field) {
            $model = new EntityFieldStructure();
            foreach ($this->fieldPropertyMappings as $name => $method) {
                if (isset($field[$name])) {
                    $model->{$method}($field[$name]);
                }
            }
            $structure->addField($model);
        }
    }
}
