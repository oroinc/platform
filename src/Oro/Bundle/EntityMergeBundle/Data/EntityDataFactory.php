<?php

namespace Oro\Bundle\EntityMergeBundle\Data;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;

use Oro\Bundle\EntityMergeBundle\Event\CreateEntityDataEvent;
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
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @param MetadataRegistry $metadataRegistry
     * @param DoctrineHelper   $doctrineHelper
     * @param EventDispatcher  $eventDispatcher
     */
    public function __construct(
        MetadataRegistry $metadataRegistry,
        DoctrineHelper $doctrineHelper,
        EventDispatcher $eventDispatcher
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
