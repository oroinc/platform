<?php

namespace Oro\Component\Config\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;

abstract class CumulativeFileLoader implements CumulativeResourceLoader
{
    /**
     * @var string
     */
    protected $relativeFilePath;

    /**
     * @var string
     *
     * not serializable. it sets in setRelativeFilePath method
     */
    protected $resource;

    /**
     * @var string
     *
     * not serializable. it sets in setRelativeFilePath method
     */
    protected $resourceName;

    /**
     * @param string $relativeFilePath The relative path to a resource file starts from bundle folder
     */
    public function __construct($relativeFilePath)
    {
        $this->setRelativeFilePath($relativeFilePath);
    }

    /**
     * Gets relative path to a resource file
     *
     * @return string
     */
    public function getRelativeFilePath()
    {
        return $this->relativeFilePath;
    }

    /**
     * Sets relative path to a resource file
     *
     * @param string $relativeFilePath The relative path to a resource file starts from bundle folder
     */
    public function setRelativeFilePath($relativeFilePath)
    {
        $relativeFilePath   = str_replace('\\', '/', $relativeFilePath);
        $delim              = strrpos($relativeFilePath, '/');
        $this->resourceName = pathinfo(
            false === $delim ? $relativeFilePath : substr($relativeFilePath, $delim + 1),
            PATHINFO_FILENAME
        );
        $path               = DIRECTORY_SEPARATOR === '/'
            ? $relativeFilePath
            : str_replace('/', DIRECTORY_SEPARATOR, $relativeFilePath);
        if (strpos($relativeFilePath, '/') === 0) {
            $this->resource         = substr($relativeFilePath, 1);
            $this->relativeFilePath = $path;
        } else {
            $this->resource         = $relativeFilePath;
            $this->relativeFilePath = DIRECTORY_SEPARATOR . $path;
        }
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
    public function load($bundleClass, $bundleDir)
    {
        $path = $bundleDir . $this->relativeFilePath;
        if (!is_file($path)) {
            return null;
        }

        $realPath = realpath($path);

        return new CumulativeResourceInfo(
            $bundleClass,
            $this->resourceName,
            $realPath,
            $this->loadFile($realPath)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function registerFoundResource($bundleClass, $bundleDir, CumulativeResource $resource)
    {
        $path = $bundleDir . $this->relativeFilePath;
        if (is_file($path)) {
            $resource->addFound($bundleClass, $path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isResourceFresh($bundleClass, $bundleDir, CumulativeResource $resource, $timestamp)
    {
        $path = $bundleDir . $this->relativeFilePath;
        if ($resource->isFound($bundleClass, $path)) {
            // check exists and removed resource
            return is_file($path) && filemtime($path) < $timestamp;
        } else {
            // check new resource
            return !is_file($path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->relativeFilePath);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->setRelativeFilePath(unserialize($serialized));
    }

    /**
     * Loads a file
     *
     * @param string $file A real path to a file
     * @return array|null
     */
    abstract protected function loadFile($file);
}
