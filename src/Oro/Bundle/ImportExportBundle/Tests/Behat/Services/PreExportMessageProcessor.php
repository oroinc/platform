<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Behat\Services;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessor as BasePreExportMessageProcessor;

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

    /** @var CacheProvider */
    private $cache;

    protected function getBatchSize(): int
    {
        $cache = $this->getCache();
        if (!$cache->contains(self::BATCH_SIZE_KEY)) {
            return parent::getBatchSize();
        }
        $batchSize = $cache->fetch(self::BATCH_SIZE_KEY);
        // Clear the cache, as these values should not affect other behat tests.
        $cache->delete(self::BATCH_SIZE_KEY);

        return $batchSize;
    }

    public function getCache(): CacheProvider
    {
        return $this->cache;
    }

    public function setCache(CacheProvider $cache): void
    {
        $this->cache = $cache;
    }
}
