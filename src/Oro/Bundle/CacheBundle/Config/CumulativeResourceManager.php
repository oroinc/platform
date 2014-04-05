<?php

namespace Oro\Bundle\CacheBundle\Config;

use Oro\Bundle\CacheBundle\Config\Loader\CumulativeLoader;
use Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader;
use Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoaderResolver;
use Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoaderWithFreshChecker;

class CumulativeResourceManager
{
    /**
     * The singleton instance
     *
     * @var CumulativeResourceManager
     */
    private static $instance = null;

    /**
     * @var array
     */
    private $bundles = [];

    /**
     * @var CumulativeResourceLoaderResolver
     */
    private $resourceLoaderResolver;

    /**
     * @var CumulativeLoader[]
     */
    private $loaders = [];

    /**
     * @var int
     */
    private $checkTimestamp;

    /**
     * @var array
     */
    private $checkResult;

    /**
     * Returns the singleton instance
     *
     * @return CumulativeResourceManager
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * A private constructor to prevent create an instance of this class explicitly
     */
    private function __construct()
    {
    }

    /**
     * Clears a state of this manager
     *
     * @return CumulativeResourceManager
     */
    public function clear()
    {
        $this->bundles                = [];
        $this->resourceLoaderResolver = null;
        $this->loaders                = [];
        $this->checkTimestamp         = null;
        $this->checkResult            = null;

        return $this;
    }

    /**
     * Gets a list of available bundles
     *
     * @return array
     */
    public function getBundles()
    {
        return $this->bundles;
    }

    /**
     * Sets a list of available bundles
     *
     * @param array $bundles
     * @return CumulativeResourceManager
     */
    public function setBundles($bundles)
    {
        $this->bundles = $bundles;
        foreach ($this->loaders as $loader) {
            $loader->setBundles($this->bundles);
        }

        return $this;
    }

    /**
     * @return CumulativeResourceLoaderResolver
     */
    public function getResourceLoaderResolver()
    {
        if (null === $this->resourceLoaderResolver) {
            $this->resourceLoaderResolver = new CumulativeResourceLoaderResolver();
        }

        return $this->resourceLoaderResolver;
    }

    /**
     * @param CumulativeResourceLoaderResolver $resourceLoaderResolver
     * @return CumulativeResourceManager
     */
    public function setResourceLoaderResolver(CumulativeResourceLoaderResolver $resourceLoaderResolver)
    {
        $this->resourceLoaderResolver = $resourceLoaderResolver;

        return $this;
    }

    /**
     * @param string                               $resourceGroup The name of a resource group
     * @param mixed|CumulativeResourceLoader|array $resource      Resource(s) or resource loader(s)
     * @return CumulativeResourceManager
     */
    public function registerResource($resourceGroup, $resource)
    {
        if (!isset($this->loaders[$resourceGroup])) {
            $loader = new CumulativeLoader();
            $loader->setBundles($this->bundles);
            $this->loaders[$resourceGroup] = $loader;
        }

        if (!is_array($resource)) {
            $resource = [$resource];
        }
        foreach ($resource as $res) {
            if (!($res instanceof CumulativeResourceLoader)) {
                $res = $this->getResourceLoaderResolver()->resolve($res);
            }
            $this->loaders[$resourceGroup]->addResourceLoader($res);
        }

        return $this;
    }

    /**
     * @param string $resourceGroup The name of a resource group
     * @return CumulativeLoader
     * @throws \RuntimeException if a loader was not found
     */
    public function getLoader($resourceGroup)
    {
        if (!isset($this->loaders[$resourceGroup])) {
            throw new \RuntimeException(
                sprintf(
                    'A loader for "%s" was not found. Please make sure you call'
                    . ' CumulativeResourceManager::getInstance()->registerResource()'
                    . ' in a constructor of a bundle responsible for accumulation of this resource.',
                    $resourceGroup
                )
            );
        }

        return $this->loaders[$resourceGroup];
    }

    /**
     * Returns true if the resource has not been updated since the given timestamp.
     *
     * @param mixed $resource   The resource
     * @param int    $timestamp The last time the resource was loaded
     *
     * @return bool true if the resource has not been updated, false otherwise
     */
    public function isFresh($resource, $timestamp)
    {
        if ($this->checkTimestamp !== $timestamp) {
            $this->checkTimestamp = $timestamp;
            $this->checkResult    = [];
            $bundles = [];
            foreach ($this->bundles as $bundleClass) {
                $reflection = new \ReflectionClass($bundleClass);
                $bundles[$bundleClass] = dirname($reflection->getFilename());
            }
            foreach ($this->loaders as $loader) {
                $resourceLoaders = $loader->getResourceLoaders();
                foreach ($resourceLoaders as $resourceLoader) {
                    if ($resourceLoader instanceof CumulativeResourceLoaderWithFreshChecker) {
                        $currentResource = $resourceLoader->getResource();
                        foreach ($bundles as $bundleClass => $bundleDir) {
                            if (!isset($this->checkResult[$currentResource])) {
                                if (!$resourceLoader->isResourceFresh($bundleClass, $bundleDir, $timestamp)) {
                                    $this->checkResult[$currentResource] = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        return !isset($this->checkResult[$resource]);
    }
}
