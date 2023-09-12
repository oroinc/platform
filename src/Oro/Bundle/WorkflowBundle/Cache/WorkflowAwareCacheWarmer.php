<?php

namespace Oro\Bundle\WorkflowBundle\Cache;

use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms workflow entity aware cache.
 */
class WorkflowAwareCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var WorkflowAwareCache
     */
    private $workflowAwareCache;

    public function __construct(WorkflowAwareCache $workflowAwareCache)
    {
        $this->workflowAwareCache = $workflowAwareCache;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir): array
    {
        $this->workflowAwareCache->build();
        return [];
    }

    /**
     * {inheritdoc}
     */
    public function isOptional(): bool
    {
        return true;
    }
}
