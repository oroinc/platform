<?php

namespace Oro\Component\Config\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
 *  $resources = $configLoader->load($container);
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
     * @param ContainerBuilder|null $container The container builder
     *                                         If NULL the loaded resources will not be registered in the container
     *                                         and as result will not be monitored for changes
     *
     * @return CumulativeResourceInfo[]
     */
    public function load(ContainerBuilder $container = null): array
    {
        $result = [];

        $bundles = CumulativeResourceManager::getInstance()->getBundles();
        $appRootDir = CumulativeResourceManager::getInstance()->getAppRootDir();

        foreach ($bundles as $bundleName => $bundleClass) {
            $bundleDir = $this->getBundleDir($bundleClass);
            $bundleAppDir = $this->getBundleAppDir($bundleName, $appRootDir);

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

        if ($container) {
            $this->registerResources($container);
        }

        return $result;
    }

    /**
     * Adds a resource objects to the container.
     * These objects will be used to monitor whether resources are up-to-date or not.
     *
     * @param ContainerBuilder $container
     */
    public function registerResources(ContainerBuilder $container): void
    {
        $container->addResource($this->getResources());
    }

    /**
     * Gets CumulativeResource object that can be used to monitor whether resources are up-to-date or not.
     *
     * @return CumulativeResource
     */
    public function getResources(): CumulativeResource
    {
        $bundles = CumulativeResourceManager::getInstance()->getBundles();
        $appRootDir = CumulativeResourceManager::getInstance()->getAppRootDir();

        $resource = new CumulativeResource($this->name, $this->resourceLoaders);
        /** @var CumulativeResourceLoader $resourceLoader */
        foreach ($this->resourceLoaders as $resourceLoader) {
            foreach ($bundles as $bundleName => $bundleClass) {
                $bundleDir = $this->getBundleDir($bundleClass);
                $bundleAppDir = $this->getBundleAppDir($bundleName, $appRootDir);

                $resourceLoader->registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, $resource);
            }
        }

        return $resource;
    }

    /**
     * @param string $bundleClass
     *
     * @return string
     */
    private function getBundleDir(string $bundleClass): string
    {
        $reflection = new \ReflectionClass($bundleClass);

        return dirname($reflection->getFileName());
    }

    /**
     * @param string      $bundleName
     * @param string|null $appRootDir
     *
     * @return string
     */
    private function getBundleAppDir(string $bundleName, ?string $appRootDir): string
    {
        return $appRootDir && is_dir($appRootDir)
            ? $appRootDir . '/Resources/' . $bundleName
            : '';
    }
}
