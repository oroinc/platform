<?php

namespace Oro\Bundle\CacheBundle\EventListener;

use Oro\Bundle\CacheBundle\Provider\ConfigCacheWarmerInterface;
use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Check are config resources is fresh and warm up the cache if needed
 */
class CacheWarmerListener
{
    /** @var \SplObjectStorage */
    protected $cacheMap;

    public function __construct()
    {
        $this->cacheMap = new \SplObjectStorage();
    }

    /**
     * @param ConfigCacheWarmerInterface $configProvider
     * @param ConfigMetadataDumperInterface $dumper
     */
    public function addConfigCacheProvider(
        ConfigCacheWarmerInterface $configProvider,
        ConfigMetadataDumperInterface $dumper
    ) {
        $this->cacheMap->attach($configProvider, $dumper);
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        //only for master request to avoid multiple calls
        if ($event->isMasterRequest()) {
            $this->validateCache();
        }
    }

    public function onConsoleCommand()
    {
        $this->validateCache();
    }

    /**
     * Executes event on kernel request and console command
     */
    protected function validateCache()
    {
        $dumpers = new \SplObjectStorage();

        /** @var ConfigCacheWarmerInterface $configProvider */
        foreach ($this->cacheMap as $configProvider) {
            /** @var ConfigMetadataDumperInterface $dumper */
            $dumper = $this->cacheMap[$configProvider];

            if (!$dumper->isFresh()) {
                $temporaryContainer = $dumpers->contains($dumper) ? $dumpers[$dumper] : new ContainerBuilder();
                $configProvider->warmUpResourceCache($temporaryContainer);

                if (!$dumpers->contains($dumper)) {
                    $dumpers->attach($dumper, $temporaryContainer);
                }
            }
        }

        foreach ($dumpers as $dumper) {
            $temporaryContainer = $dumpers[$dumper];
            $dumper->dump($temporaryContainer);
        }
    }
}
