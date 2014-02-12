<?php

namespace Oro\Bundle\EntityMergeBundle\Data;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;

use Oro\Bundle\EntityMergeBundle\Event\CreateEntityDataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory;

class EntityDataFactory
{
    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @param MetadataFactory $metadataFactory
     * @param DoctrineHelper $doctrineHelper
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(
        MetadataFactory $metadataFactory,
        DoctrineHelper $doctrineHelper,
        EventDispatcher $eventDispatcher
    ) {
        $this->metadataFactory = $metadataFactory;
        $this->doctrineHelper  = $doctrineHelper;
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

        $data = new EntityData($entityMetadata, $entities);

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
        $entities = $this->doctrineHelper->getEntitiesByIds($entityName, $entityIds);
        return $this->createEntityData($entityName, $entities);
    }
}
