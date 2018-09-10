<?php

namespace Oro\Bundle\SecurityBundle\Acl\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

/**
 * This cache stores OIDs for which object level ACL does not exists
 */
class UnderlyingAclCache
{
    /** @var CacheProvider */
    protected $cache;

    /**
     * Local storage of loaded entity underlying OIDs batches
     *
     * @var array
     */
    protected $entityBatches = [];

    /**
     * Local storage of loaded underlying OIDs batches
     *
     * @var array
     */
    protected $loadedBatches = [];

    /**
     * @var int How many ids will be stored in one batch
     */
    protected $batchSize = 1000;

    /**
     * @param CacheProvider $cacheProvider
     * @param int           $batchSize
     */
    public function __construct(CacheProvider $cacheProvider, $batchSize = 1000)
    {
        $this->cache = $cacheProvider;
        $this->batchSize = $batchSize;
    }

    /**
     * Caches underlying OID
     *
     * @param ObjectIdentityInterface $oid
     */
    public function cacheUnderlying(ObjectIdentityInterface $oid)
    {
        $batchNumber = $this->getBatchNumber($oid);
        $batchKey = $this->getBatchCacheKey($oid);
        $type = $oid->getType();
        
        if (!array_key_exists($type, $this->entityBatches)) {
            $this->entityBatches[$type] = [];
        }

        if (!array_key_exists($type, $this->loadedBatches)
            || !array_key_exists($batchNumber, $this->loadedBatches[$type])
        ) {
            $batch = $this->cache->fetch($batchKey);
            if (false === $batch) {
                $batch = [];
            }
            $this->loadedBatches[$type][$batchNumber] = $batch;
        }

        // set info that given id is underlined
        $this->loadedBatches[$type][$batchNumber][$oid->getIdentifier()] = true;

        //check if we have full underlyied batch
        if (count($this->loadedBatches[$type][$batchNumber]) === $this->batchSize) {
            $this->entityBatches[$type][$batchNumber] = true;
            $this->cache->save($type, $this->entityBatches[$type]);
        }

        $this->cache->save($batchKey, $this->loadedBatches[$type][$batchNumber]);
    }

    /**
     * Checks if given OID is underlying
     *
     * @param ObjectIdentityInterface $oid
     *
     * @return bool
     */
    public function isUnderlying(ObjectIdentityInterface $oid)
    {
        if (!$this->isDigitIdentifier($oid)) {
            return false;
        }

        $batchNumber = $this->getBatchNumber($oid);
        $batchKey = $this->getBatchCacheKey($oid);
        $type = $oid->getType();
        
        // check if batches info loaded and load it
        if (!array_key_exists($type, $this->entityBatches)) {
            $batch = $this->cache->fetch($type);
            if (false === $batch) {
                $batch = [];
            }
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
            $batch = $this->cache->fetch($batchKey);
            if (false === $batch) {
                $batch = [];
            }
            $this->loadedBatches[$type][$batchNumber] = $batch;
        }

        return array_key_exists($oid->getIdentifier(), $this->loadedBatches[$type][$batchNumber]);
    }

    /**
     * Removes OID info from the cache
     *
     * @param ObjectIdentityInterface $oid
     */
    public function evictFromCache(ObjectIdentityInterface $oid)
    {
        if (!$this->isDigitIdentifier($oid)) {
            $this->cache->delete($this->getUnderlyingDataKeyByIdentity($oid));
        } else {
            $batchNumber = $this->getBatchNumber($oid);
            $type = $oid->getType();

            if (array_key_exists($type, $this->entityBatches)) {
                unset($this->entityBatches[$type]);
            }
            $this->cache->delete($type);

            if (array_key_exists($type, $this->loadedBatches)
                && array_key_exists($batchNumber, $this->loadedBatches[$type])
            ) {
                unset($this->loadedBatches[$type][$batchNumber]);
            }
            $this->cache->delete($this->getBatchCacheKey($oid));
        }
    }

    /**
     * Clear all underlying OIDs cache
     */
    public function clearCache()
    {
        $this->cache->deleteAll();
    }

    /**
     * Returns batch number for given OID
     *
     * @param ObjectIdentityInterface $oid
     *
     * @return int
     */
    protected function getBatchNumber(ObjectIdentityInterface $oid)
    {
        $identifier = $oid->getIdentifier();
        /**
         * We can't correctly calculate batch number in case when "id" is not an integer,
         * so we put this entities to the single batch
         */
        if (!$this->isDigitIdentifier($oid)) {
            return 1;
        }
        return (int)floor($identifier / $this->batchSize) + 1;
    }

    /**
     * Returns batch cache key
     *
     * @param ObjectIdentityInterface $oid
     *
     * @return string
     */
    protected function getBatchCacheKey(ObjectIdentityInterface $oid)
    {
        return $oid->getType() . '_' . $this->getBatchNumber($oid);
    }

    /**
     * Returns the key for the object identity.
     *
     * @param ObjectIdentityInterface $oid
     *
     * @return string
     */
    protected function getUnderlyingDataKeyByIdentity(ObjectIdentityInterface $oid)
    {
        return $oid->getType() . '_' . $oid->getIdentifier();
    }

    /**
     * Check if OID identifier contains only digits
     *
     * @param  ObjectIdentityInterface $oid
     *
     * @return boolean
     */
    protected function isDigitIdentifier(ObjectIdentityInterface $oid)
    {
        return is_int($oid->getIdentifier()) || ctype_digit($oid->getIdentifier());
    }
}
