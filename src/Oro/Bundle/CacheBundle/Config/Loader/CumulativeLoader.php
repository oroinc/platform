<?php

namespace Oro\Bundle\CacheBundle\Config\Loader;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\CacheBundle\Config\CumulativeResource;
use Oro\Bundle\CacheBundle\Config\CumulativeResourceInfo;

class CumulativeLoader
{
    /**
     * @var CumulativeLoaderHolder
     */
    protected $holder;

    /**
     * @var CumulativeResourceLoader[]
     */
    protected $resourceLoaders = [];

    /**
     * @param CumulativeLoaderHolder $holder
     */
    public function __construct(CumulativeLoaderHolder $holder)
    {
        $this->holder = $holder;
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
     * @param ContainerBuilder $container
     * @return CumulativeResourceInfo[]
     */
    public function load(ContainerBuilder $container)
    {
        $result = [];

        $bundles = $this->holder->getBundles();
        foreach ($bundles as $bundle) {
            foreach ($this->resourceLoaders as $resourceLoader) {
                $resource = $resourceLoader->load($bundle);
                if ($resource) {
                    $result[] = $resource;
                }
            }
        }

        $this->registerResources($container);

        return $result;
    }

    /**
     * @param ContainerBuilder $container
     */
    public function registerResources(ContainerBuilder $container)
    {
        foreach ($this->resourceLoaders as $resourceLoader) {
            $container->addResource(new CumulativeResource($resourceLoader->getResource()));
        }
    }
}
