<?php

namespace Oro\Bundle\CacheBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Oro\Bundle\CacheBundle\Provider\ConfigCacheWarmerInterface;

class CacheWarmerListener
{
    /** @var \SplObjectStorage */
    protected $cacheMap;

    /** @var bool */
    protected $kernelDebug;

    /**
     * @param bool $kernelDebug
     */
    public function __construct($kernelDebug = false)
    {
        $this->kernelDebug = $kernelDebug;
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
     * Check cache metadata and warm up cache if needed
     */
    public function onKernelRequest()
    {
        if (false === $this->kernelDebug) {
            return;
        }

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
