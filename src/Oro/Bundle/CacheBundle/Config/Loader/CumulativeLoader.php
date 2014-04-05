<?php

namespace Oro\Bundle\CacheBundle\Config\Loader;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceInfo;

class CumulativeLoader
{
    /**
     * @var array
     */
    protected $bundles;

    /**
     * @var CumulativeResourceLoader[]
     */
    protected $resourceLoaders = [];

    /**
     * Sets a list of available bundles
     *
     * @param array $bundles
     */
    public function setBundles($bundles)
    {
        $this->bundles = $bundles;
    }

    /**
     * @param CumulativeResourceLoader $resourceLoader
     * @return CumulativeLoader
     */
    public function addResourceLoader(CumulativeResourceLoader $resourceLoader)
    {
        $this->resourceLoaders[] = $resourceLoader;

        return $this;
    }

    /**
     * @return CumulativeResourceLoader[]
     */
    public function getResourceLoaders()
    {
        return $this->resourceLoaders;
    }

    /**
     * @param ContainerBuilder|null $container
     * @return CumulativeResourceInfo[]
     */
    public function load(ContainerBuilder $container = null)
    {
        $result = [];

        foreach ($this->bundles as $bundleClass) {
            $reflection = new \ReflectionClass($bundleClass);
            $bundleDir  = dirname($reflection->getFilename());
            foreach ($this->resourceLoaders as $resourceLoader) {
                $resource = $resourceLoader->load($bundleClass, $bundleDir);
                if ($resource) {
                    $result[] = $resource;
                }
            }
        }

        if ($container) {
            $this->registerResources($container);
        }

        return $result;
    }

    /**
     * @param ContainerBuilder $container
     */
    public function registerResources(ContainerBuilder $container)
    {
        foreach ($this->resourceLoaders as $resourceLoader) {
            $resourceLoader->registerResource($container);
        }
    }
}
