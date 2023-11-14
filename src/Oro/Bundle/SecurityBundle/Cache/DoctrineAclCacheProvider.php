<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Doctrine\Common\Cache\Cache;

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

    public function getCurrentUserCache(): Cache
    {
        return $this->getCache();
    }

    /**
     * Clears all Doctrine ACL caches.
     */
    public function clear(): void
    {
        $cacheNamespacesCache = $this->cacheInstantiator->getCacheInstance(self::CACHE_NAMESPACES);
        $batchList = $cacheNamespacesCache->fetch(self::ITEMS_LIST_HEY);
        if (false === $batchList) {
            $batchList = [];
        }
        foreach ($batchList as $entityClass => $batchNumbers) {
            foreach ($batchNumbers as $batchNumber) {
                $batchKey = $this->getBatchCacheKeyByBatchNumber($entityClass, $batchNumber);
                $batchData = $cacheNamespacesCache->fetch($batchKey);
                foreach (array_keys($batchData) as $entityId) {
                    $cache = $this->getCache($entityClass, (int)$entityId, false);
                    $cache->deleteAll();
                }
            }
        }
        $cacheNamespacesCache->deleteAll();
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
        $cache->deleteAll();

        $cacheNamespacesCache = $this->cacheInstantiator->getCacheInstance(self::CACHE_NAMESPACES);
        $batchKey = $this->getBatchCacheKey($entityClass, $entityId);

        $batch = $cacheNamespacesCache->fetch($batchKey);
        $isBatchItemShouldBeRemoved = false;
        if (false !== $batch) {
            unset($batch[$entityId]);

            if (count($batch) === 0) {
                $cacheNamespacesCache->delete($batchKey);
                $isBatchItemShouldBeRemoved = true;
            } else {
                $cacheNamespacesCache->save($batchKey, $batch);
            }
        }

        if ($isBatchItemShouldBeRemoved) {
            $batchList = $cacheNamespacesCache->fetch(self::ITEMS_LIST_HEY);
            if (false === $batchList) {
                $batchList = [];
            }
            if (isset($batchList[$entityClass])) {
                $userBatchId = $this->getBatchNumber($entityId);
                foreach ($batchList[$entityClass] as $key => $batchId) {
                    if ((int)$batchId === $userBatchId) {
                        unset($batchList[$entityClass][$key]);
                        $cacheNamespacesCache->save(self::ITEMS_LIST_HEY, $batchList);
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
    ): Cache {
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
        $batch = $cacheNamespacesCache->fetch($batchKey);
        $knownBatch = false;

        if (false !== $batch && count($batch) > 0) {
            $knownBatch = true;
        } else {
            $batch = [];
        }

        if (!\array_key_exists($entityId, $batch)) {
            $batch[$entityId] = true;
            $cacheNamespacesCache->save($batchKey, $batch);
        }

        if (!$knownBatch) {
            $batchList = $cacheNamespacesCache->fetch(self::ITEMS_LIST_HEY);
            if (false === $batchList) {
                $batchList = [];
            }
            if (!\array_key_exists($entityClass, $batchList)) {
                $batchList[$entityClass] = [];
            }
            $batchNumber = $this->getBatchNumber($entityId);
            if (!\in_array($batchNumber, $batchList[$entityClass], true)) {
                $batchList[$entityClass][] = $this->getBatchNumber($entityId);
                $cacheNamespacesCache->save(self::ITEMS_LIST_HEY, $batchList);
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
        return substr($entityClass, strrpos($entityClass, '\\') +1);
    }
}
