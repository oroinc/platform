<?php

namespace Oro\Component\Config\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * This loader can be used to load configuration files that can be located in any bundle.
 * E.g. to load configuration from "Resources\config\acme.yml" files from all bundles
 * the following code can be used:
 * <code>
 *  $configLoader = new CumulativeConfigLoader(
 *      'acme_config',
 *      new YamlCumulativeFileLoader('Resources/config/acme.yml')
 *  );
 *  $acmeConfig = [];
 *  $resources = $configLoader->load(new ContainerBuilderAdapter($container));
 *  foreach ($resources as $resource) {
 *      $acmeConfig = array_merge($acmeConfig, $resource->data);
 *  }
 * </code>
 */
class CumulativeConfigLoader
{
    /** @var string */
    private $name;

    /** @var CumulativeResourceLoaderCollection */
    private $resourceLoaders;

    /**
     * @param string                                              $name The unique name of a configuration resource
     * @param CumulativeResourceLoader|CumulativeResourceLoader[] $resourceLoader
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $name, $resourceLoader)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('$name must not be empty.');
        }
        if (empty($resourceLoader)) {
            throw new \InvalidArgumentException('$resourceLoader must not be empty.');
        }

        $this->name = $name;
        $this->resourceLoaders = new CumulativeResourceLoaderCollection(
            is_array($resourceLoader) ? $resourceLoader : [$resourceLoader]
        );
    }

    /**
     * Loads resources
     *
     * @param ResourcesContainerInterface|null $resourcesContainer
     *
     * @return CumulativeResourceInfo[]
     */
    public function load(ResourcesContainerInterface $resourcesContainer = null): array
    {
        $result = [];

        $manager = CumulativeResourceManager::getInstance();
        $bundles = $manager->getBundles();
        foreach ($bundles as $bundleName => $bundleClass) {
            $bundleDir = $manager->getBundleDir($bundleClass);
            $bundleAppDir = $manager->getBundleAppDir($bundleName);
            /** @var CumulativeResourceLoader $resourceLoader */
            foreach ($this->resourceLoaders as $resourceLoader) {
                $resource = $resourceLoader->load($bundleClass, $bundleDir, $bundleAppDir);
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

        if ($resourcesContainer) {
            $this->registerResources($resourcesContainer);
        }

        return $result;
    }

    /**
     * Adds a resource objects to the container.
     * These objects will be used to monitor whether resources are up-to-date or not.
     */
    public function registerResources(ResourcesContainerInterface $resourcesContainer): void
    {
        $resourcesContainer->addResource($this->getResources());
    }

    /**
     * Gets CumulativeResource object that can be used to monitor whether resources are up-to-date or not.
     */
    public function getResources(): CumulativeResource
    {
        $manager = CumulativeResourceManager::getInstance();
        $bundles = $manager->getBundles();
        $resource = new CumulativeResource($this->name, $this->resourceLoaders);
        foreach ($bundles as $bundleName => $bundleClass) {
            $bundleDir = $manager->getBundleDir($bundleClass);
            $bundleAppDir = $manager->getBundleAppDir($bundleName);
            /** @var CumulativeResourceLoader $resourceLoader */
            foreach ($this->resourceLoaders as $resourceLoader) {
                $resourceLoader->registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, $resource);
            }
        }

        return $resource;
    }
}
