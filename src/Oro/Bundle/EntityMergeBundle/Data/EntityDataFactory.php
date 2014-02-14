<?php

namespace Oro\Bundle\EntityMergeBundle\Data;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataRegistry;

class EntityDataFactory
{
    /**
     * @var MetadataRegistry
     */
    private $metadataRegistry;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param MetadataRegistry $metadataRegistry
     * @param DoctrineHelper $doctrineHelper
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        MetadataRegistry $metadataRegistry,
        DoctrineHelper $doctrineHelper,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->metadataRegistry = $metadataRegistry;
        $this->doctrineHelper   = $doctrineHelper;
        $this->eventDispatcher  = $eventDispatcher;
    }

    /**
     * @param string $entityName
     * @param array  $entities
     * @return EntityData
     */
    public function createEntityData($entityName, array $entities)
    {
        $entityMetadata = $this->metadataRegistry->getEntityMetadata($entityName);

        $data = new EntityData($entityMetadata, $entities);

        $this->eventDispatcher->dispatch(
            MergeEvents::CREATE_ENTITY_DATA,
            new EntityDataEvent($data)
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
