<?php

namespace Oro\Bundle\ReportBundle\EventListener;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\ReportBundle\Entity\Report;

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

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$entity instanceof Report) {
            return;
        }

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
