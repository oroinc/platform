<?php

namespace Oro\Bundle\LoggerBundle\Cache;

use Monolog\Handler\DeduplicationHandler;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * This warmer flushes buffering for DeduplicationHandler.
 *
 * Should be called in the end of all warmup caches operations, because the logs for the handler are written
 * to the intermediate cache folder , it happens late and at this moment file for logs doesn't exist.
 */
class DeduplicationHandlerCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var DeduplicationHandler|null
     */
    private $deduplicationHandler;

    /**
     * @param DeduplicationHandler $deduplicationHandler
     */
    public function __construct(DeduplicationHandler $deduplicationHandler = null)
    {
        $this->deduplicationHandler = $deduplicationHandler;
    }

    /**
     * {inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        if ($this->deduplicationHandler) {
            $this->deduplicationHandler->flush();
        }
    }

    /**
     * {inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }
}
