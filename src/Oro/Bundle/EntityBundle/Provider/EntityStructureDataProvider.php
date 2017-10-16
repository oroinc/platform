<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityStructureDataProvider
{
    const EVENT_OPTIONS = 'oro_entity.structure.options';

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var $entityWithFieldsProvider */
    protected $entityWithFieldsProvider;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityWithFieldsProvider $entityWithFieldsProvider
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityWithFieldsProvider $entityWithFieldsProvider
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->entityWithFieldsProvider = $entityWithFieldsProvider;
    }

    /**
     * @return array|EntityStructure[]
     */
    public function getData()
    {
        $entityStructures = $this->processEntities();

        $event = new EntityStructureOptionsEvent();
        $event->setData($entityStructures);

        return $this->eventDispatcher->dispatch(self::EVENT_OPTIONS, $event)->getData();
    }

    /**
     * @return array|EntityStructure[]
     */
    protected function processEntities()
    {
        $entities = $this->entityWithFieldsProvider->getFields(
            true,
            true,
            true,
            false,
            true,
            true
        );
        $result = [];

        $mappings = [
            'name' => 'setClassName',
            'label' => 'setLabel',
            'plural_label' => 'setPluralLabel',
            'icon' => 'setIcon',
            'routes' => 'setRoutes',
        ];

        foreach ($entities as $entity) {
            $model = new EntityStructure();
            foreach ($mappings as $name => $method) {
                if (isset($entity[$name])) {
                    $model->{$method}($entity[$name]);
                }
            }
            if (!empty($entity['fields'])) {
                $this->processFields($model, $entity['fields']);
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
        $mappings = [
            'name' => 'setName',
            'type' => 'setType',
            'label' => 'setLabel',
            'relation_type' => 'setRelationType',
            'related_entity_name' => 'setRelatedEntityName',
        ];
        foreach ($fields as $field) {
            $model = new EntityFieldStructure();
            foreach ($mappings as $name => $method) {
                if (isset($field[$name])) {
                    $model->{$method}($field[$name]);
                }
            }
            $structure->addField($model);
        }
    }
}
