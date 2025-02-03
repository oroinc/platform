<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Doctrine ACL query cache provider that stores data in separate caches.
 */
class DoctrineAclCacheProvider
{
    private const CACHE_FORMAT = 'doctrine_acl_%s_%s';
    private const CACHE_NAMESPACES = 'doctrine_acl_namespaces';
    private const ITEMS_LIST_HEY = 'itemsList';

    private CacheInstantiatorInterface $cacheInstantiator;
    private DoctrineAclCacheUserInfoProviderInterface $aclCacheUserInfoProvider;

    public function __construct(
        CacheInstantiatorInterface $cacheInstantiator,
        DoctrineAclCacheUserInfoProviderInterface $aclCacheUserInfoProvider
    ) {
        $this->cacheInstantiator = $cacheInstantiator;
        $this->aclCacheUserInfoProvider = $aclCacheUserInfoProvider;
    }

    public function getCurrentUserCache(): AdapterInterface
    {
        return $this->getCache();
    }

    /**
     * Clears all Doctrine ACL caches.
     */
    public function clear(): void
    {
        $cacheNamespacesCache = $this->cacheInstantiator->getCacheInstance(self::CACHE_NAMESPACES);
        $batchCacheItemsList = $cacheNamespacesCache->getItem(self::ITEMS_LIST_HEY);
        $batchList = $batchCacheItemsList->isHit() ? $batchCacheItemsList->get() : [];
        foreach ($batchList as $entityClass => $batchNumbers) {
            foreach ($batchNumbers as $batchNumber) {
                $batchKey = $this->getBatchCacheKeyByBatchNumber($entityClass, $batchNumber);
                $batchCacheItem = $cacheNamespacesCache->getItem($batchKey);
                $batchData = $batchCacheItem->isHit() ? $batchCacheItem->get() : [];
                foreach (array_keys($batchData) as $entityId) {
                    $cache = $this->getCache($entityClass, (int)$entityId, false);
                    $cache->clear();
                }
            }
        }
        $cacheNamespacesCache->clear();
    }

    /**
     * Clears cache for given entities.
     */
    public function clearForEntities(string $entityClass, array $entityIds): void
    {
        foreach ($entityIds as $entityId) {
            if ($entityId) {
                $this->clearForEntity($entityClass, (int)$entityId);
            }
        }
    }

    /**
     * Clears cache for given entity.
     */
    public function clearForEntity($entityClass, $entityId): void
    {
        $cache = $this->getCache($entityClass, $entityId, false);
        $cache->clear();

        $cacheNamespacesCache = $this->cacheInstantiator->getCacheInstance(self::CACHE_NAMESPACES);
        $batchKey = $this->getBatchCacheKey($entityClass, $entityId);
        $batchCacheItem = $cacheNamespacesCache->getItem($batchKey);
        $isBatchItemShouldBeRemoved = false;
        if ($batchCacheItem->isHit()) {
            $batch = $batchCacheItem->get();
            unset($batch[$entityId]);
            if (count($batch) === 0) {
                $cacheNamespacesCache->deleteItem($batchKey);
                $isBatchItemShouldBeRemoved = true;
            } else {
                $batchCacheItem->set($batch);
                $cacheNamespacesCache->save($batchCacheItem);
            }
        }

        if ($isBatchItemShouldBeRemoved) {
            $batchCacheItemsList = $cacheNamespacesCache->getItem(self::ITEMS_LIST_HEY);
            $batchList = $batchCacheItemsList->isHit() ? $batchCacheItemsList->get() : [];
            if (isset($batchList[$entityClass])) {
                $entityBatchId = $this->getBatchNumber($entityId);
                foreach ($batchList[$entityClass] as $key => $batchId) {
                    if ((int)$batchId === $entityBatchId) {
                        unset($batchList[$entityClass][$key]);
                        $batchCacheItemsList->set($batchList);
                        $cacheNamespacesCache->save($batchCacheItemsList);
                        break;
                    }
                }
            }
        }
    }

    private function getCache(
        ?string $entityClass = null,
        ?int $entityId = null,
        bool $ensureCacheIsKnown = true
    ): AdapterInterface {
        if (null === $entityClass && null === $entityId) {
            [$entityClass, $entityId] = $this->aclCacheUserInfoProvider->getCurrentUserCacheKeyInfo();
        }

        $key = sprintf(self::CACHE_FORMAT, $this->getCacheClassRepresentation($entityClass), $entityId);
        $cache = $this->cacheInstantiator->getCacheInstance($key);
        if ($ensureCacheIsKnown) {
            $this->ensureCacheNamespaceIsKnown($entityClass, $entityId);
        }

        return $cache;
    }

    private function ensureCacheNamespaceIsKnown($entityClass, $entityId): void
    {
        $cacheNamespacesCache = $this->cacheInstantiator->getCacheInstance(self::CACHE_NAMESPACES);
        $batchKey = $this->getBatchCacheKey($entityClass, $entityId);
        $batchCacheItem = $cacheNamespacesCache->getItem($batchKey);
        $batch = [];
        $knownBatch = false;

        if ($batchCacheItem->isHit()) {
            $batch = $batchCacheItem->get();
            $knownBatch = true;
        }

        if (!\array_key_exists($entityId, $batch)) {
            $batch[$entityId] = true;
            $batchCacheItem->set($batch);
            $cacheNamespacesCache->save($batchCacheItem);
        }

        if (!$knownBatch) {
            $batchCacheItemsList = $cacheNamespacesCache->getItem(self::ITEMS_LIST_HEY);
            $batchList = $batchCacheItemsList->isHit() ? $batchCacheItemsList->get() : [];
            if (!\array_key_exists($entityClass, $batchList)) {
                $batchList[$entityClass] = [];
            }
            $batchNumber = $this->getBatchNumber($entityId);
            if (!\in_array($batchNumber, $batchList[$entityClass], true)) {
                $batchList[$entityClass][] = $this->getBatchNumber($entityId);
                $batchCacheItemsList->set($batchList);
                $cacheNamespacesCache->save($batchCacheItemsList);
            }
        }
    }

    private function getBatchNumber($id): int
    {
        return (int)floor((int)$id / 1000) + 1;
    }

    private function getBatchCacheKey($entityClass, $entityId): string
    {
        return $this->getBatchCacheKeyByBatchNumber($entityClass, $this->getBatchNumber($entityId));
    }

    private function getBatchCacheKeyByBatchNumber($entityClass, $batchNumber): string
    {
        return $this->getCacheClassRepresentation($entityClass) . '_' . $batchNumber;
    }

    private function getCacheClassRepresentation(string $entityClass): string
    {
        return substr($entityClass, strrpos($entityClass, '\\') + 1);
    }
}
