<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Oro\Bundle\InstallerBundle\CommandExecutor;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate as BaseCacheWarmerAggregate;

class CacheWarmerAggregate implements CacheWarmerInterface
{
    /** @var BaseCacheWarmerAggregate */
    private $baseCacheWarmerAggregate;

    /** @var CacheWarmerInterface[] Warmers required for extend bundle */
    private $warmers;

    /**
     * @param BaseCacheWarmerAggregate $baseCacheWarmerAggregate
     */
    public function __construct(BaseCacheWarmerAggregate $baseCacheWarmerAggregate)
    {
        $this->baseCacheWarmerAggregate = $baseCacheWarmerAggregate;
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
            || CommandExecutor::isCurrentCommand('oro:platform:upgrade', true)) {
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
