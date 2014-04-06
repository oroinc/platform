<?php

namespace Oro\Component\Config\Loader;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;

abstract class CumulativeFileLoader implements CumulativeResourceLoaderWithFreshChecker
{
    /**
     * @var string
     */
    protected $relativeFilePath;

    /**
     * @var string
     */
    protected $resource;

    /**
     * @var string
     */
    protected $resourceName;

    /**
     * @param string $relativeFilePath The name of a file starts from bundle folder
     */
    public function __construct($relativeFilePath)
    {
        $delim              = strrpos($relativeFilePath, '/');
        $this->resourceName = pathinfo(
            false === $delim ? $relativeFilePath : substr($relativeFilePath, $delim + 1),
            PATHINFO_FILENAME
        );
        $this->resource     = $relativeFilePath;
        if (DIRECTORY_SEPARATOR !== '/') {
            $relativeFilePath = str_replace('/', DIRECTORY_SEPARATOR, $relativeFilePath);
        }
        $this->relativeFilePath = DIRECTORY_SEPARATOR . $relativeFilePath;
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
    public function registerResource(ContainerBuilder $container)
    {
        $container->addResource(new CumulativeResource($this->getResource()));
    }

    /**
     * {@inheritdoc}
     */
    public function isResourceFresh($bundleClass, $bundleDir, $timestamp)
    {
        $path = $bundleDir . $this->relativeFilePath;

        return !is_file($path) || filemtime($path) < $timestamp;
    }

    /**
     * @param string $path A real path to a file
     * @return array
     */
    abstract protected function loadFile($path);
}
