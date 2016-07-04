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
    public function load($bundleClass, $bundleDir, $bundleAppDir = '')
    {
        $realPath = $this->getResourcePath($bundleAppDir, $bundleDir);

        if ($realPath === null) {
            return null;
        }

        return new CumulativeResourceInfo(
            $bundleClass,
            $this->resourceName,
            $realPath,
            $this->doLoad($realPath)
        );
    }

    /**
     * @param string $realPath
     * @return array
     */
    protected function doLoad($realPath)
    {
        $data = $this->loadFile($realPath);
        
        if (!is_array($data)) {
            return [];
        }
        
        return (array)$data;
    }

    /**
     * Returns realpath for source file if file exists or null if file does not exists.
     * Priority loading remains for the $bundleAppDir.
     *
     * @param string $bundleAppDir The bundle directory inside the application resources directory
     * @param string $bundleDir    The bundle root directory
     *
     * @return string|null
     */
    public function getResourcePath($bundleAppDir, $bundleDir)
    {
        $path = $this->getBundleAppResourcePath($bundleAppDir);
        if ($path === null) {
            $path = $this->getBundleResourcePath($bundleDir);
        }

        return $path;
    }

    /**
     * Returns realpath for source file in the $bundleAppDir directory if file exists
     * or null if file does not exists
     *
     * @param string $bundleAppDir
     *
     * @return string|null
     */
    protected function getBundleAppResourcePath($bundleAppDir)
    {
        if (is_dir($bundleAppDir)) {
            $path = $this->normalizeBundleAppDir($bundleAppDir);
            if (is_file($path)) {
                return realpath($path);
            }
        }

        return null;
    }

    /**
     * Returns realpath for source file in the <Bundle> Resources directory if file exists
     * or null if file does not exists
     *
     * @param string $bundleDir The bundle root directory
     *
     * @return string|null
     */
    protected function getBundleResourcePath($bundleDir)
    {
        $path = $bundleDir . $this->relativeFilePath;
        if (is_file($path)) {
            return realpath($path);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, CumulativeResource $resource)
    {
        $path = $this->getBundleAppResourcePath($bundleAppDir);
        if (is_file($path)) {
            $resource->addFound($bundleClass, $path);
        } else {
            $path = $this->getBundleResourcePath($bundleDir);
            if (is_file($path)) {
                $resource->addFound($bundleClass, $path);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, CumulativeResource $resource, $timestamp)
    {
        if (is_dir($bundleAppDir)) {
            $path = $this->normalizeBundleAppDir($bundleAppDir);
            if ($resource->isFound($bundleClass, $path)) {
                // check exists and removed resource
                return is_file($path) && filemtime($path) < $timestamp;
            }
            // check new resource
            if (is_file($path)) {
                return false;
            }
        }

        $path = $bundleDir . $this->relativeFilePath;
        if ($resource->isFound($bundleClass, $path)) {
            // check exists and removed resource
            return is_file($path) && filemtime($path) < $timestamp;
        }

        // check new resource
        return !is_file($path);
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
     *
     * @return array|null
     */
    abstract protected function loadFile($file);

    /**
     * @param string $bundleAppDir
     *
     * @return string
     */
    protected function normalizeBundleAppDir($bundleAppDir)
    {
        return
            $bundleAppDir
            . preg_replace('/Resources\\' . DIRECTORY_SEPARATOR . '/', '', $this->relativeFilePath, 1);
    }
}
