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

    /**
     * {@inheritdoc}
     */
    public function clear($cacheDir)
    {
        $this->workflowAwareCache->invalidateRelated();
        $this->workflowAwareCache->invalidateActiveRelated();
    }
}
