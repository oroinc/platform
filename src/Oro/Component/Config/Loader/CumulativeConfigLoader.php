<?php

namespace Oro\Component\Config\Loader;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\CumulativeResourceManager;

class CumulativeConfigLoader
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * @param ContainerBuilder|null $container
     */
    public function __construct(ContainerBuilder $container = null)
    {
        $this->container = $container;
    }

    /**
     * Loads resources of the given group
     *
     * @param string $resourceGroup The name of a resource group
     * @return CumulativeResourceInfo[]
     */
    public function load($resourceGroup)
    {
        $result = [];

        $bundles         = CumulativeResourceManager::getInstance()->getBundles();
        $resourceLoaders = CumulativeResourceManager::getInstance()->getResourceLoaders($resourceGroup);
        foreach ($bundles as $bundleClass) {
            $reflection = new \ReflectionClass($bundleClass);
            $bundleDir  = dirname($reflection->getFilename());
            foreach ($resourceLoaders as $resourceLoader) {
                $resource = $resourceLoader->load($bundleClass, $bundleDir);
                if (null !== $resource) {
                    if (is_array($resource)) {
                        foreach ($resource as $res) {
                            $result[] = $res;
                        }
                    } else {
                        $result[] = $resource;
                    }
                }
            }
        }

        if ($this->container) {
            $this->registerResources($resourceGroup);
        }

        return $result;
    }

    /**
     * Adds a resource objects to the container.
     * These objects will be used to check whether resources of the given group are up-to-date or not.
     *
     * @param string $resourceGroup The name of a resource group
     * @throws \RuntimeException if the container builder was not specified
     */
    public function registerResources($resourceGroup)
    {
        if (!$this->container) {
            throw new \RuntimeException('The container builder must not be null.');
        }

        $bundles         = CumulativeResourceManager::getInstance()->getBundles();
        $resourceLoaders = CumulativeResourceManager::getInstance()->getResourceLoaders($resourceGroup);
        $resource        = new CumulativeResource($resourceGroup);
        foreach ($resourceLoaders as $resourceLoader) {
            foreach ($bundles as $bundleClass) {
                $reflection = new \ReflectionClass($bundleClass);
                $bundleDir  = dirname($reflection->getFilename());
                $resourceLoader->registerFoundResource($bundleClass, $bundleDir, $resource);
            }
        }
        $this->container->addResource($resource);
    }
}
