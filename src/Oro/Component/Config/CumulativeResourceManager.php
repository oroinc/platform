<?php

namespace Oro\Component\Config;

use Oro\Component\Config\Loader\CumulativeResourceLoader;
use Oro\Component\Config\Loader\CumulativeResourceLoaderWithFreshChecker;

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
     * @var array of CumulativeResourceLoader[]
     *            key   = resource group
     *            value = CumulativeResourceLoader[]
     */
    private $resourceLoaders = [];

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
        $this->resourceLoaders        = [];
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

        return $this;
    }

    /**
     * Adds resource loaders for the given resource group
     *
     * @param string                                              $resourceGroup   The name of a resource group
     * @param CumulativeResourceLoader|CumulativeResourceLoader[] $resourceLoader Resource loader(s)
     * @return CumulativeResourceManager
     */
    public function addResourceLoader($resourceGroup, $resourceLoader)
    {
        if (!isset($this->resourceLoaders[$resourceGroup])) {
            $this->resourceLoaders[$resourceGroup] = [];
        }

        if (is_array($resourceLoader)) {
            foreach ($resourceLoader as $loader) {
                $this->resourceLoaders[$resourceGroup][] = $loader;
            }
        } else {
            $this->resourceLoaders[$resourceGroup][] = $resourceLoader;
        }

        return $this;
    }

    /**
     * Gets all resource loaders for the given resource group
     *
     * @param string $resourceGroup The name of a resource group
     * @return CumulativeResourceLoader[]
     * @throws \RuntimeException if a loader was not found
     */
    public function getResourceLoaders($resourceGroup)
    {
        if (!isset($this->resourceLoaders[$resourceGroup])) {
            throw new \RuntimeException(
                sprintf(
                    'Resource loaders for "%s" was not found. Please make sure you call'
                    . ' CumulativeResourceManager::getInstance()->addResourceLoader()'
                    . ' in a constructor of a bundle responsible for accumulation of this resource.',
                    $resourceGroup
                )
            );
        }

        return $this->resourceLoaders[$resourceGroup];
    }

    /**
     * Returns true if the resource has not been updated since the given timestamp.
     *
     * @param mixed $resource  The resource
     * @param int   $timestamp The last time the resource was loaded
     *
     * @return bool TRUE if the resource has not been updated; otherwise, FALSE
     */
    public function isFresh($resource, $timestamp)
    {
        if ($this->checkTimestamp !== $timestamp) {
            $this->checkTimestamp = $timestamp;
            $this->checkResult    = [];
            $bundles              = [];
            foreach ($this->bundles as $bundleClass) {
                $reflection            = new \ReflectionClass($bundleClass);
                $bundles[$bundleClass] = dirname($reflection->getFilename());
            }
            foreach ($this->resourceLoaders as $resourceLoaders) {
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
