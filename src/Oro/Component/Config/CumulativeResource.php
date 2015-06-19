<?php

namespace Oro\Component\Config;

use Oro\Component\Config\Loader\CumulativeResourceLoader;
use Oro\Component\Config\Loader\CumulativeResourceLoaderCollection;
use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * CumulativeResource represents a resource which can be located in any bundle
 * and does not required any special registration in a bundle.
 */
class CumulativeResource implements ResourceInterface, \Serializable
{
    /**
     * @var string
     */
    protected $resource;

    /**
     * The list of found the resource
     *
     * @var array
     *      key   = bundle class
     *      value = array
     *          key   = resource path
     *          value = TRUE
     */
    protected $found = [];

    /**
     * @var int
     *
     * not serializable
     */
    protected $isFreshTimestamp;

    /**
     * @var int|false
     *
     * not serializable
     */
    protected $isFresh;

    /**
     * @var CumulativeResourceLoaderCollection
     */
    private $resourceLoaders;

    /**
     * @param string                             $resource        The unique name of a configuration resource
     * @param CumulativeResourceLoaderCollection $resourceLoaders The resource loaders
     */
    public function __construct($resource, CumulativeResourceLoaderCollection $resourceLoaders)
    {
        $this->resource        = $resource;
        $this->resourceLoaders = $resourceLoaders;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($timestamp)
    {
        if ($this->isFreshTimestamp !== $timestamp) {
            $this->isFreshTimestamp = $timestamp;
            $this->isFresh          = true;

            $bundles = CumulativeResourceManager::getInstance()->getBundles();
            $appRootDir = CumulativeResourceManager::getInstance()->getAppRootDir();
            foreach ($bundles as $bundleName => $bundleClass) {
                $reflection = new \ReflectionClass($bundleClass);
                $bundleDir  = dirname($reflection->getFilename());

                $bundleAppDir = '';
                /**
                 * This case needs for tests(without app root directory).
                 */
                if (is_dir($appRootDir)) {
                    $bundleAppDir = $appRootDir . '/Resources/' . $bundleName;
                }
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
     * Registers a resource as found one
     *
     * @param string $bundleClass The full name of bundle class
     * @param string $path        The full path to the resource
     */
    public function addFound($bundleClass, $path)
    {
        if (!isset($this->found[$bundleClass])) {
            $this->found[$bundleClass] = [];
        }
        $this->found[$bundleClass][$path] = true;
    }

    /**
     * Checks if a resource was registered as found one
     *
     * @param string $bundleClass The full name of bundle class
     * @param string $path        The full path to the resource
     * @return bool
     */
    public function isFound($bundleClass, $path)
    {
        return isset($this->found[$bundleClass][$path]);
    }

    /**
     * Gets all found resources for the given bundle
     *
     * @param string $bundleClass The full name of bundle class
     * @return string[] A list of resources' full paths
     */
    public function getFound($bundleClass)
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

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->resource, $this->found, $this->resourceLoaders]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->resource, $this->found, $this->resourceLoaders) = unserialize($serialized);
    }
}
