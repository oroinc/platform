<?php

namespace Oro\Bundle\EntityMergeBundle\Data;

use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Creates EntityData instances from entity names and entity collections.
 *
 * This factory is responsible for instantiating {@see EntityData} objects that encapsulate
 * the entities to be merged along with their associated merge metadata. It dispatches
 * the `CREATE_ENTITY_DATA` event to allow listeners to modify the entity data before
 * it is returned to the caller.
 */
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
            new EntityDataEvent($data),
            MergeEvents::CREATE_ENTITY_DATA
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
