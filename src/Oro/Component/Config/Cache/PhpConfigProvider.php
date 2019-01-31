<?php

namespace Oro\Component\Config\Cache;

use Oro\Component\Config\ResourcesContainer;
use Oro\Component\Config\ResourcesContainerInterface;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The base class for configuration that should be stored in a PHP file.
 */
abstract class PhpConfigProvider implements WarmableConfigCacheInterface, ClearableConfigCacheInterface
{
    /** @var string */
    private $cacheFile;

    /** @var ConfigCacheFactoryInterface */
    private $configCacheFactory;

    /** @var PhpConfigCacheAccessor */
    private $cacheAccessor;

    /** @var mixed|null */
    private $config;

    /**
     * @param string                      $cacheFile
     * @param ConfigCacheFactoryInterface $configCacheFactory
     */
    public function __construct(string $cacheFile, ConfigCacheFactoryInterface $configCacheFactory)
    {
        $this->cacheFile = $cacheFile;
        $this->configCacheFactory = $configCacheFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        $this->config = null;
        if (\is_file($this->cacheFile)) {
            $fs = new Filesystem();
            $fs->remove($this->cacheFile);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache(): void
    {
        $this->clearCache();
        $this->ensureCacheWarmedUp();
    }

    /**
     * Makes sure that configuration cache was warmed up.
     */
    public function ensureCacheWarmedUp(): void
    {
        if (null === $this->config) {
            $cache = $this->configCacheFactory->cache($this->cacheFile, function (ConfigCacheInterface $cache) {
                $resourcesContainer = new ResourcesContainer();
                $config = $this->doLoadConfig($resourcesContainer);
                $this->getCacheAccessor()->save($cache, $config, $resourcesContainer->getResources());
            });

            $this->config = $this->getCacheAccessor()->load($cache);
        }
    }

    /**
     * @return mixed
     */
    protected function doGetConfig()
    {
        $this->ensureCacheWarmedUp();

        return $this->config;
    }

    /**
     * @param ResourcesContainerInterface $resourcesContainer
     *
     * @return mixed
     */
    abstract protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer);

    /**
     * @param mixed $config
     *
     * @throws \LogicException if the given config is not valid
     */
    abstract protected function assertLoaderConfig($config): void;

    /**
     * @return PhpConfigCacheAccessor
     */
    private function getCacheAccessor(): PhpConfigCacheAccessor
    {
        if (null === $this->cacheAccessor) {
            $this->cacheAccessor = new PhpConfigCacheAccessor(function ($config) {
                $this->assertLoaderConfig($config);
            });
        }

        return $this->cacheAccessor;
    }
}
