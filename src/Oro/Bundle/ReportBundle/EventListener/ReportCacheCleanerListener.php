<?php

namespace Oro\Bundle\ReportBundle\EventListener;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\ReportBundle\Entity\Report;

/**
 * Class ReportCacheCleanerListener
 */
class ReportCacheCleanerListener
{
    /**
     * @var Cache
     */
    private $reportCacheManager;

    /**
     * @var string
     */
    protected $prefixCacheKey;

    /**
     * ReportCacheCleanerListener constructor.
     *
     * @param Cache  $reportCacheManager
     * @param string $prefixCacheKey
     */
    public function __construct(
        Cache $reportCacheManager,
        $prefixCacheKey
    ) {
        $this->reportCacheManager = $reportCacheManager;
        $this->prefixCacheKey = $prefixCacheKey;
    }

    /**
     * @param Report             $entity
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(Report $entity, LifecycleEventArgs $args)
    {
        $this->clearCache($entity);
    }

    /**
     * @param Report $entity
     */
    protected function clearCache(Report $entity)
    {
        $key = $this->prefixCacheKey.'.'.$entity->getGridPrefix().$entity->getId();

        if ($this->reportCacheManager->contains($key)) {
            $this->reportCacheManager->delete($key);
        }
    }
}
