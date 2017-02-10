<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate as BaseCacheWarmerAggregate;

use Oro\Bundle\EntityBundle\Tools\CheckDatabaseStateManager;
use Oro\Bundle\InstallerBundle\CommandExecutor;

class CacheWarmerAggregate implements CacheWarmerInterface
{
    /** @var BaseCacheWarmerAggregate */
    private $baseCacheWarmerAggregate;

    /** @var CacheWarmerInterface[] Warmers required for extend bundle */
    private $warmers;

    /**
     * @param BaseCacheWarmerAggregate  $baseCacheWarmerAggregate
     * @param CheckDatabaseStateManager $checkDatabaseStateManager
     */
    public function __construct(
        BaseCacheWarmerAggregate $baseCacheWarmerAggregate,
        CheckDatabaseStateManager $checkDatabaseStateManager
    ) {
        $this->baseCacheWarmerAggregate = $baseCacheWarmerAggregate;
        $checkDatabaseStateManager->clearState();
    }

    /** {@inheritdoc} */
    public function enableOptionalWarmers()
    {
        $this->baseCacheWarmerAggregate->enableOptionalWarmers();
    }

    /** {@inheritdoc} */
    public function isOptional()
    {
        return $this->baseCacheWarmerAggregate->isOptional();
    }

    /**
     * {@inheritdoc}
     *
     * Do not warmup caches if extend caches are not up to date
     */
    public function warmUp($cacheDir)
    {
        if (CommandExecutor::isCurrentCommand('oro:entity-extend:cache:', true)
            || CommandExecutor::isCurrentCommand('oro:install', true)
            || CommandExecutor::isCurrentCommand('oro:platform:upgrade20', true)
        ) {
            foreach ($this->warmers as $warmer) {
                $warmer->warmUp($cacheDir);
            }

            return;
        }

        $this->baseCacheWarmerAggregate->warmUp($cacheDir);
    }

    /**
     * @param CacheWarmerInterface $cacheWarmer
     */
    public function addWarmer(CacheWarmerInterface $cacheWarmer)
    {
        $this->warmers[] = $cacheWarmer;
    }
}
