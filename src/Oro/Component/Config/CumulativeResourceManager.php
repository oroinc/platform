<?php

namespace Oro\Component\Config;

use Oro\Component\Config\Loader\CumulativeResourceLoader;

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
     * @var array
     *      key   = resource group
     *      value = CumulativeResourceLoader[]
     */
    private $resourceLoaders = [];

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
     * Clears state of this manager
     *
     * @return CumulativeResourceManager
     */
    public function clear()
    {
        $this->bundles         = [];
        $this->resourceLoaders = [];

        return $this;
    }

    /**
     * Gets list of available bundles
     *
     * @return array
     */
    public function getBundles()
    {
        return $this->bundles;
    }

    /**
     * Sets list of available bundles
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
     * @param string                                              $resourceGroup  The name of a resource group
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
}
