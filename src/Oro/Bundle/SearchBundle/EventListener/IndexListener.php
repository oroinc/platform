<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

class IndexListener implements OptionalListenerInterface
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
     * @deprecated since 1.8 Please use mappingProvider for mapping config
     */
    protected $entitiesConfig = [];

    /**
     * @var SearchMappingProvider
     */
    protected $mappingProvider;

    /**
     * @var array
     */
    protected $savedEntities = [];

    /**
     * @var array
     */
    protected $deletedEntities = [];

    /**
     * @var bool
     */
    protected $enabled = true;

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
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
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
     * @deprecated since 1.8 Please use mappingProvider for mapping config
     */
    public function setEntitiesConfig(array $entities)
    {
        $this->entitiesConfig = $entities;
    }

    /**
     * @param SearchMappingProvider $mappingProvider
     */
    public function setMappingProvider(SearchMappingProvider $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $entityManager = $args->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        // schedule saved entities
        // inserted and updated entities should be processed as is
        $savedEntities = array_merge(
            $unitOfWork->getScheduledEntityInsertions(),
            $this->getEntitiesWithUpdatedIndexedFields($unitOfWork)
        );
        foreach ($savedEntities as $hash => $entity) {
            if (empty($this->savedEntities[$hash]) && $this->isSupported($entity)) {
                $this->savedEntities[$hash] = $entity;
            }
        }

        // schedule deleted entities
        // deleted entities should be processed as references because on postFlush they are already deleted
        $deletedEntities = $unitOfWork->getScheduledEntityDeletions();
        foreach ($deletedEntities as $hash => $entity) {
            if (empty($this->deletedEntities[$hash]) && $this->isSupported($entity)) {
                $this->deletedEntities[$hash] = $entityManager->getReference(
                    $this->doctrineHelper->getEntityClass($entity),
                    $this->doctrineHelper->getSingleEntityIdentifier($entity)
                );
            }
        }
    }

    /**
     * @param UnitOfWork $uow
     *
     * @return object[]
     */
    protected function getEntitiesWithUpdatedIndexedFields(UnitOfWork $uow)
    {
        $entitiesToReindex = [];

        foreach ($uow->getScheduledEntityUpdates() as $hash => $entity) {
            $className = ClassUtils::getClass($entity);
            if (!$this->mappingProvider->isFieldsMappingExists($className)) {
                continue;
            }

            $entityConfig = $this->mappingProvider->getEntityConfig($className);

            $indexedFields = [];
            foreach ($entityConfig['fields'] as $fieldConfig) {
                $indexedFields[] = $fieldConfig['name'];
            }

            $changeSet = $uow->getEntityChangeSet($entity);
            $fieldsToReindex = array_intersect($indexedFields, array_keys($changeSet));
            if ($fieldsToReindex) {
                $entitiesToReindex[$hash] = $entity;
            }
        }

        return $entitiesToReindex;
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        if ($this->hasEntitiesToIndex()) {
            $this->indexEntities();
        }
    }

    /**
     * Clear object storage when error was occurred during UOW#Commit
     *
     * @param OnClearEventArgs $args
     */
    public function onClear(OnClearEventArgs $args)
    {
        if (!($this->enabled && $this->hasEntitiesToIndex())) {
            return;
        }

        $this->savedEntities = $this->deletedEntities = [];
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
        return $this->mappingProvider->isClassSupported(ClassUtils::getClass($entity));
    }

    /**
     * @return bool
     */
    protected function hasEntitiesToIndex()
    {
        return !empty($this->savedEntities) || !empty($this->deletedEntities);
    }
}
