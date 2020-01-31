<?php

namespace Oro\Bundle\ScopeBundle\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeEntityStorage;

/**
 * The listener that does the following:
 * * on preFlush event calls EntityManager::persist() method for all Scope entities scheduled for insert
 * * on postFlush event invalidates scope cache when a Scope entity is created, updated or deleted
 *   or when a target entity of any association in Scope entity is deleted
 */
class DoctrineEventListener
{
    /** @var ScopeEntityStorage */
    private $entityStorage;

    /** @var CacheProvider */
    private $scopeRepositoryCache;

    /** @var bool */
    private $needToResetScopeCache = false;

    /**
     * @param ScopeEntityStorage $entityStorage
     * @param CacheProvider      $scopeRepositoryCache
     */
    public function __construct(ScopeEntityStorage $entityStorage, CacheProvider $scopeRepositoryCache)
    {
        $this->entityStorage = $entityStorage;
        $this->scopeRepositoryCache = $scopeRepositoryCache;
    }

    public function preFlush()
    {
        $this->entityStorage->persistScheduledForInsert();
        $this->entityStorage->clear();
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        if ($this->needToResetScopeCache) {
            // do nothing as we are already known that the cache should be reset
            return;
        }

        if ($this->isScopeCacheAffected($event->getEntityManager())) {
            $this->needToResetScopeCache = true;
        }
    }

    public function postFlush()
    {
        if ($this->needToResetScopeCache) {
            $this->needToResetScopeCache = false;
            $this->scopeRepositoryCache->deleteAll();
        }
    }

    public function onClear()
    {
        $this->needToResetScopeCache = false;
        $this->entityStorage->clear();
    }

    /**
     * @param EntityManagerInterface $em
     *
     * @return bool
     */
    private function isScopeCacheAffected(EntityManagerInterface $em): bool
    {
        $uow = $em->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Scope) {
                return true;
            }
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Scope) {
                return true;
            }
        }
        $scheduledEntityDeletions = $uow->getScheduledEntityDeletions();
        if ($scheduledEntityDeletions) {
            $scopeEntityClasses = $this->getScopeEntityClasses($em);
            foreach ($scheduledEntityDeletions as $entity) {
                if (isset($scopeEntityClasses[ClassUtils::getClass($entity)])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param EntityManagerInterface $em
     *
     * @return array [entity class => TRUE, ...]
     */
    private function getScopeEntityClasses(EntityManagerInterface $em): array
    {
        $result = [];
        $result[Scope::class] = true;
        $scopeMetadata = $em->getClassMetadata(Scope::class);
        $associations = $scopeMetadata->getAssociationMappings();
        foreach ($associations as $association) {
            $targetEntity = $association['targetEntity'];
            if (!isset($result[$targetEntity])) {
                $result[$targetEntity] = true;
            }
        }

        return $result;
    }
}
