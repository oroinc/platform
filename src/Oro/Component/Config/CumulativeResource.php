<?php

namespace Oro\Component\Config;

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
    private $resourceGroup;

    /**
     * The list of found the resource
     *
     * @var array
     *      key   = bundle class
     *      value = array
     *          key   = resource path
     *          value = TRUE
     */
    private $found = [];

    /**
     * @var int
     *
     * not serializable
     */
    private $isFreshTimestamp;

    /**
     * @var int|false
     *
     * not serializable
     */
    private $isFresh;

    /**
     * @param string $resourceGroup The name of a resource group
     */
    public function __construct($resourceGroup)
    {
        $this->resourceGroup = $resourceGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->resourceGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($timestamp)
    {
        if ($this->isFreshTimestamp !== $timestamp) {
            $this->isFreshTimestamp = $timestamp;
            $this->isFresh    = true;

            $bundles = CumulativeResourceManager::getInstance()->getBundles();
            $loaders = CumulativeResourceManager::getInstance()->getResourceLoaders($this->resourceGroup);
            foreach ($bundles as $bundleClass) {
                $reflection = new \ReflectionClass($bundleClass);
                $bundleDir  = dirname($reflection->getFilename());
                foreach ($loaders as $loader) {
                    if (!$loader->isResourceFresh($bundleClass, $bundleDir, $this, $timestamp)) {
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
        return $this->resourceGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->resourceGroup, $this->found]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->resourceGroup, $this->found) = unserialize($serialized);
    }
}
