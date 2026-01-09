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

    #[\Override]
    public function warmUp($cacheDir, ?string $buildDir = null): array
    {
        $this->workflowAwareCache->build();
        return [];
    }

    /**
     * {inheritdoc}
     */
    #[\Override]
    public function isOptional(): bool
    {
        return true;
    }
}
