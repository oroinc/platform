<?php

namespace Oro\Bundle\EntityMergeBundle\Data;

use Oro\Bundle\EntityMergeBundle\Event\CreateEntityDataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EntityDataFactory
{
    /**
     * @var MetadataFactory $metadataFactory
     */
    private $metadataFactory;

    /**
     * @var EntityProvider $entityProvider
     */
    private $entityProvider;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @param MetadataFactory $metadataFactory
     * @param EntityProvider  $entityProvider
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(
        MetadataFactory $metadataFactory,
        EntityProvider $entityProvider,
        EventDispatcher $eventDispatcher
    ) {
        $this->metadataFactory = $metadataFactory;
        $this->entityProvider  = $entityProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $entityName
     * @param array  $entities
     * @return EntityData
     */
    public function createEntityData($entityName, array $entities)
    {
        $entityMetadata = $this->metadataFactory->createMergeMetadata($entityName);

        $data = new EntityData($entityMetadata);
        $data->setEntities($entities);
        foreach ($entityMetadata->getFieldsMetadata() as $fieldMetadata) {
            $data->addNewField($fieldMetadata);
        }

        $this->eventDispatcher->dispatch(
            MergeEvents::CREATE_ENTITYDATA,
            new CreateEntityDataEvent($data)
        );

        return $data;
    }

    /**
     * @param string $entityName
     * @param array  $entityIds
     * @return EntityData
     */
    public function createEntityDataByIds($entityName, array $entityIds)
    {
        $entities = $this->entityProvider->getEntitiesByIds($entityName, $entityIds);
        return $this->createEntityData($entityName, $entities);
    }
}
