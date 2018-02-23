<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Oro\Bundle\EntityBundle\Tools\CheckDatabaseStateManager;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate as CacheWarmer;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class CacheWarmerAggregate implements CacheWarmerInterface
{
    /** @var ServiceLink */
    private $cacheWarmerLink;

    /** @var ServiceLink */
    private $extendCacheWarmerLink;

    /** @var bool */
    private $optionalsEnabled = false;

    /**
     * @param ServiceLink               $cacheWarmerLink
     * @param ServiceLink               $extendCacheWarmerLink
     * @param CheckDatabaseStateManager $checkDatabaseStateManager
     */
    public function __construct(
        ServiceLink $cacheWarmerLink,
        ServiceLink $extendCacheWarmerLink,
        CheckDatabaseStateManager $checkDatabaseStateManager
    ) {
        $this->cacheWarmerLink = $cacheWarmerLink;
        $this->extendCacheWarmerLink = $extendCacheWarmerLink;
        $checkDatabaseStateManager->clearState();
    }

    /**
     * Requests the execution of optional warmers during the warming up the cache.
     */
    public function enableOptionalWarmers()
    {
        $this->optionalsEnabled = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * Do not warmup caches if extend caches are not up to date
     */
    public function warmUp($cacheDir)
    {
        $cacheWarmerLink = $this->cacheWarmerLink;
        if (CommandExecutor::isCurrentCommand('oro:entity-extend:cache:', true)
            || CommandExecutor::isCurrentCommand('oro:install', true)
            || CommandExecutor::isCurrentCommand('oro:platform:upgrade20', true)
        ) {
            $cacheWarmerLink = $this->extendCacheWarmerLink;
        }
        /** @var CacheWarmer $cacheWarmer */
        $cacheWarmer = $cacheWarmerLink->getService();
        if ($this->optionalsEnabled) {
            $cacheWarmer->enableOptionalWarmers();
        }
        $cacheWarmer->warmUp($cacheDir);
    }
}
