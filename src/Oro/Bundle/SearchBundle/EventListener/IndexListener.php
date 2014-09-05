<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class IndexListener
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EngineInterface
     */
    protected $searchEngine;

    /**
     * @var bool
     */
    protected $realTimeUpdate = true;

    /**
     * @var array
     */
    protected $entitiesConfig = [];

    /**
     * @var array
     */
    protected $savedEntities = [];

    /**
     * @var array
     */
    protected $deletedEntities = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EngineInterface $searchEngine
     */
    public function __construct(DoctrineHelper $doctrineHelper, EngineInterface $searchEngine)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->searchEngine   = $searchEngine;
    }

    /**
     * @param $realTime bool
     */
    public function setRealTimeUpdate($realTime)
    {
        $this->realTimeUpdate = $realTime;
    }

    /**
     * @param array $entities
     */
    public function setEntitiesConfig(array $entities)
    {
        $this->entitiesConfig = $entities;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $entityManager = $args->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        // schedule saved entities
        // inserted and updated entities should be processed as is
        $savedEntities = array_merge(
            $unitOfWork->getScheduledEntityInsertions(),
            $unitOfWork->getScheduledEntityUpdates()
        );
        foreach ($savedEntities as $hash => $entity) {
            if ($this->isSupported($entity) && empty($this->savedEntities[$hash])) {
                $this->savedEntities[$hash] = $entity;
            }
        }

        // schedule deleted entities
        // deleted entities should be processed as references because on postFlush they are already deleted
        $deletedEntities = $unitOfWork->getScheduledEntityDeletions();
        foreach ($deletedEntities as $hash => $entity) {
            if ($this->isSupported($entity) && empty($this->deletedEntities[$hash])) {
                $this->deletedEntities[$hash] = $entityManager->getReference(
                    $this->doctrineHelper->getEntityClass($entity),
                    $this->doctrineHelper->getSingleEntityIdentifier($entity)
                );
            }
        }
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->hasEntitiesToIndex()) {
            $this->indexEntities();
        }
    }

    /**
     * Synchronise all changed entities with search index
     */
    protected function indexEntities()
    {
        // process saved entities
        if ($this->savedEntities) {
            $savedEntities = $this->savedEntities;
            $this->savedEntities = [];
            $this->searchEngine->save($savedEntities, $this->realTimeUpdate);
        }

        // process deleted entities
        if ($this->deletedEntities) {
            $deletedEntities = $this->deletedEntities;
            $this->deletedEntities = [];
            $this->searchEngine->delete($deletedEntities, $this->realTimeUpdate);
        }
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isSupported($entity)
    {
        return isset($this->entitiesConfig[ClassUtils::getClass($entity)]);
    }

    /**
     * @return bool
     */
    protected function hasEntitiesToIndex()
    {
        return !empty($this->savedEntities) || !empty($this->deletedEntities);
    }
}
