<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @param EntityClassNameHelper $classNameHelper
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
     * @return array|EntityStructure[]
     */
    public function getData()
    {
        $entityStructures = $this->processEntities();

        $event = new EntityStructureOptionsEvent();
        $event->setData($entityStructures);

        return $this->eventDispatcher->dispatch(EntityStructureOptionsEvent::EVENT_NAME, $event)->getData();
    }

    /**
     * @return array|EntityStructure[]
     */
    protected function processEntities()
    {
        $data = $this->entityWithFieldsProvider->getFields(true, true, true, false, true, true);
        $result = [];

        foreach ($data as $item) {
            $model = new EntityStructure();
            foreach ($this->entityPropertyMappings as $name => $method) {
                if (isset($item[$name])) {
                    $model->{$method}($item[$name]);
                }
            }

            $model->setId($this->classNameHelper->getUrlSafeClassName($model->getClassName()));

            if (!empty($item['fields'])) {
                $this->processFields($model, $item['fields']);
            }
            $result[$model->getClassName()] = $model;
        }

        return $result;
    }

    /**
     * @param EntityStructure $structure
     * @param array $fields
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
