<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Behat\Services;

use Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessor as BasePreExportMessageProcessor;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Used only on CI.
 * Since it is not advisable to export a file with more than 5,000 rows,
 * it is better to reduce the size of the export batch.
 *
 * The values of the container cannot be changed during the operation of the consumer,
 * so we determine the parameters using the cache only in this case we can affected the batch size.
 */
class PreExportMessageProcessor extends BasePreExportMessageProcessor
{
    public const BATCH_SIZE_KEY = 'batch_size_key';

    private CacheItemPoolInterface $cache;

    protected function getBatchSize(): int
    {
        $cache = $this->getCache();
        $cacheItem = $cache->getItem(self::BATCH_SIZE_KEY);
        if (!$cacheItem->isHit()) {
            return parent::getBatchSize();
        }
        $batchSize = $cacheItem->get();
        // Clear the cache, as these values should not affect other behat tests.
        $cache->deleteItem(self::BATCH_SIZE_KEY);

        return $batchSize;
    }

    public function getCache(): CacheItemPoolInterface
    {
        return $this->cache;
    }

    public function setCache(CacheItemPoolInterface $cache): void
    {
        $this->cache = $cache;
    }
}
