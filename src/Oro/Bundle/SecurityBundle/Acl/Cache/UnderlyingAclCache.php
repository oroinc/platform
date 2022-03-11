<?php

namespace Oro\Bundle\SecurityBundle\Acl\Cache;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

/**
 * This cache stores OIDs for which object level ACL does not exists
 */
class UnderlyingAclCache
{
    protected CacheItemPoolInterface $cache;

    /**
     * Local storage of loaded entity underlying OIDs batches
     */
    protected array $entityBatches = [];

    /**
     * Local storage of loaded underlying OIDs batches
     */
    protected array $loadedBatches = [];

    /**
     * How many ids will be stored in one batch
     */
    protected int $batchSize = 1000;

    /**
     * @param CacheItemPoolInterface $cacheProvider
     * @param int           $batchSize
     */
    public function __construct(CacheItemPoolInterface $cacheProvider, int $batchSize = 1000)
    {
        $this->cache = $cacheProvider;
        $this->batchSize = $batchSize;
    }

    /**
     * Caches underlying OID
     */
    public function cacheUnderlying(ObjectIdentityInterface $oid): void
    {
        $batchNumber = $this->getBatchNumber($oid);
        $batchKey = $this->getBatchCacheKey($oid);
        $type = $oid->getType();
        $batchCacheItem = $this->cache->getItem($this->normalizeCacheKey($batchKey));

        if (!array_key_exists($type, $this->entityBatches)) {
            $this->entityBatches[$type] = [];
        }

        if (!array_key_exists($type, $this->loadedBatches)
            || !array_key_exists($batchNumber, $this->loadedBatches[$type])
        ) {
            $batch = $batchCacheItem->isHit() ? $batchCacheItem->get() : [];
            $this->loadedBatches[$type][$batchNumber] = $batch;
        }

        // set info that given id is underlined
        $this->loadedBatches[$type][$batchNumber][$oid->getIdentifier()] = true;

        //check if we have full underlyied batch
        if (count($this->loadedBatches[$type][$batchNumber]) === $this->batchSize) {
            $this->entityBatches[$type][$batchNumber] = true;
            $cacheItem = $this->cache->getItem($this->normalizeCacheKey($type))->set($this->entityBatches[$type]);
            $this->cache->save($cacheItem);
        }

        $batchCacheItem->set($this->loadedBatches[$type][$batchNumber]);
        $this->cache->save($batchCacheItem);
    }

    public function isUnderlying(ObjectIdentityInterface $oid): bool
    {
        $batchNumber = $this->getBatchNumber($oid);
        $batchKey = $this->getBatchCacheKey($oid);
        $type = $oid->getType();

        // check if batches info loaded and load it
        if (!array_key_exists($type, $this->entityBatches)) {
            $cacheItem = $this->cache->getItem($this->normalizeCacheKey($type));
            $batch = $cacheItem->isHit() ? $cacheItem->get() : [];
            $this->entityBatches[$type] = $batch;
        }

        // check if given batch is underlyied
        if (array_key_exists($batchNumber, $this->entityBatches[$type])) {
            return true;
        }

        // check if batch loaded and load it
        if (!array_key_exists($type, $this->loadedBatches)) {
            $this->loadedBatches[$type] = [];
        }
        if (!array_key_exists($batchNumber, $this->loadedBatches[$type])) {
            $batchCacheItem = $this->cache->getItem($this->normalizeCacheKey($batchKey));
            $batch = $batchCacheItem->isHit() ? $batchCacheItem->get() : [];
            $this->loadedBatches[$type][$batchNumber] = $batch;
        }

        return array_key_exists($oid->getIdentifier(), $this->loadedBatches[$type][$batchNumber]);
    }

    /**
     * Removes OID info from the cache
     */
    public function evictFromCache(ObjectIdentityInterface $oid): void
    {
        if (!$this->isDigitIdentifier($oid)) {
            $this->cache->deleteItem($this->normalizeCacheKey($this->getUnderlyingDataKeyByIdentity($oid)));
        } else {
            $batchNumber = $this->getBatchNumber($oid);
            $type = $oid->getType();

            if (array_key_exists($type, $this->entityBatches)) {
                unset($this->entityBatches[$type]);
            }
            $this->cache->deleteItem($this->normalizeCacheKey($type));

            if (array_key_exists($type, $this->loadedBatches)
                && array_key_exists($batchNumber, $this->loadedBatches[$type])
            ) {
                unset($this->loadedBatches[$type][$batchNumber]);
            }
            $this->cache->deleteItem($this->normalizeCacheKey($this->getBatchCacheKey($oid)));
        }
    }

    /**
     * Clear all underlying OIDs cache
     */
    public function clearCache(): void
    {
        $this->cache->clear();
    }

    /**
     * Returns batch number for given OID
     */
    protected function getBatchNumber(ObjectIdentityInterface $oid): int
    {
        $identifier = $oid->getIdentifier();

        if (!$this->isDigitIdentifier($oid)) {
            $identifier = crc32($identifier);
        }

        return (int)floor($identifier / $this->batchSize) + 1;
    }

    /**
     * Returns batch cache key
     */
    protected function getBatchCacheKey(ObjectIdentityInterface $oid): string
    {
        return $oid->getType() . '_' . $this->getBatchNumber($oid);
    }

    /**
     * Returns the key for the object identity
     */
    protected function getUnderlyingDataKeyByIdentity(ObjectIdentityInterface $oid): string
    {
        return $oid->getType() . '_' . $oid->getIdentifier();
    }

    /**
     * Check if OID identifier contains only digits
     */
    protected function isDigitIdentifier(ObjectIdentityInterface $oid): bool
    {
        return is_int($oid->getIdentifier()) || ctype_digit($oid->getIdentifier());
    }

    private function normalizeCacheKey(string $key): string
    {
        return UniversalCacheKeyGenerator::normalizeCacheKey($key);
    }
}
