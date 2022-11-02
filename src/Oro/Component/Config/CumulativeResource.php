<?php

namespace Oro\Component\Config;

use Oro\Component\Config\Loader\CumulativeResourceLoader;
use Oro\Component\Config\Loader\CumulativeResourceLoaderCollection;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;

/**
 * Represents a resource which can be located in any bundle
 * and does not required any special registration in a bundle.
 */
class CumulativeResource implements ResourceInterface, SelfCheckingResourceInterface
{
    private string $resource;
    private CumulativeResourceLoaderCollection $resourceLoaders;

    /** @var array [bundle class => [resource path => TRUE, ...], ...] */
    private array $found = [];

    /** not serializable */
    private int $isFreshTimestamp = 0;
    /** not serializable */
    private bool $isFresh = false;

    /**
     * @param string                             $resource        The unique name of a configuration resource
     * @param CumulativeResourceLoaderCollection $resourceLoaders The resource loaders
     */
    public function __construct(string $resource, CumulativeResourceLoaderCollection $resourceLoaders)
    {
        $this->resource = $resource;
        $this->resourceLoaders = $resourceLoaders;
    }

    /**
     * Gets the unique name of a configuration resource.
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh(int $timestamp): bool
    {
        if ($this->isFreshTimestamp !== $timestamp) {
            $this->isFreshTimestamp = $timestamp;
            $this->isFresh = true;

            $manager = CumulativeResourceManager::getInstance();
            $bundles = $manager->getBundles();
            foreach ($bundles as $bundleName => $bundleClass) {
                $bundleDir = $manager->getBundleDir($bundleClass);
                $bundleAppDir = $manager->getBundleAppDir($bundleName);
                /** @var CumulativeResourceLoader $loader */
                foreach ($this->resourceLoaders as $loader) {
                    if (!$loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $this, $timestamp)) {
                        $this->isFresh = false;
                        break;
                    }
                }
                if (!$this->isFresh) {
                    break;
                }
            }
        }

        return $this->isFresh;
    }

    /**
     * Registers a resource as found one.
     */
    public function addFound(string $bundleClass, string $path): void
    {
        $this->found[$bundleClass][$path] = true;
    }

    /**
     * Checks if a resource was registered as found one.
     */
    public function isFound(string $bundleClass, string $path): bool
    {
        return isset($this->found[$bundleClass][$path]);
    }

    /**
     * Gets all found resources for the given bundle
     *
     * @param string $bundleClass
     *
     * @return string[] A list of resources' full paths
     */
    public function getFound(string $bundleClass): array
    {
        return isset($this->found[$bundleClass])
            ? array_keys($this->found[$bundleClass])
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->resource;
    }

    public function __serialize(): array
    {
        return [$this->resource, $this->found, $this->resourceLoaders];
    }

    public function __unserialize(array $serialized): void
    {
        [$this->resource, $this->found, $this->resourceLoaders] = $serialized;
    }
}
