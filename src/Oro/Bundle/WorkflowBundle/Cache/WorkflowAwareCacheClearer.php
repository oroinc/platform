<?php

namespace Oro\Bundle\WorkflowBundle\Cache;

use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

/**
 * Clears workflow entity aware cache.
 */
class WorkflowAwareCacheClearer implements CacheClearerInterface
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
    public function clear($cacheDir): void
    {
        $this->workflowAwareCache->invalidateRelated();
        $this->workflowAwareCache->invalidateActiveRelated();
    }
}
