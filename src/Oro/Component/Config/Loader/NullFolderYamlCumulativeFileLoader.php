<?php

namespace Oro\Component\Config\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Symfony\Component\Finder\Finder;

/**
 * NullFolderYamlCumulativeFileLoader represents a file resource located in some folder.
 * This loader can be used if you need to find only paths of cumulative files.
 */
class NullFolderYamlCumulativeFileLoader implements CumulativeResourceLoader
{
    private array $registeredRelativeFilePaths = [];
    private YamlCumulativeFileLoader|null $fileLoader = null;

    public function __construct(
        protected string $relativeFolderPath,
        protected string $fileNamePattern = '*.yml',
        protected int $depth = 0,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function load($bundleClass, $bundleDir, $bundleAppDir = ''): ?array
    {
        $resources = [];
        $realPath = $this->getBundleResourceDirPath($bundleAppDir, $bundleDir);
        if (!is_dir($realPath)) {
            return null;
        }

        foreach ($this->getFileResourceFinder($realPath) as $configItem) {
            $relativeFilePath = $this->getRelativeFilePath($configItem->getFilename());
            $resources[] = new CumulativeResourceInfo($bundleClass, $configItem->getFilename(), $relativeFilePath);
        }

        return $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource(): string
    {
        return $this->relativeFolderPath;
    }

    /**
     * {@inheritdoc}
     */
    public function registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, CumulativeResource $resource): void
    {
        $bundleDirPath = $this->getBundleResourceDirPath($bundleAppDir, $bundleDir);
        if (!is_dir($bundleDirPath)) {
            return;
        }
        foreach ($this->getFileResourceFinder($bundleDirPath) as $configItem) {
            $relativeFilePath = $this->getRelativeFilePath($configItem->getFilename());
            $resource->addFound($bundleClass, $configItem->getPathname());
            $this->registeredRelativeFilePaths[$bundleDirPath][] = $relativeFilePath;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isResourceFresh(
        $bundleClass,
        $bundleDir,
        $bundleAppDir,
        CumulativeResource $resource,
        $timestamp
    ): bool {
        $bundleDirPath = $this->getBundleResourceDirPath($bundleAppDir, $bundleDir);
        if (!is_dir($bundleDirPath)) {
            return true;
        }
        if (!array_key_exists($bundleDirPath, $this->registeredRelativeFilePaths)) {
            return false;
        }
        $yamlLoader = $this->getYamlFileLoader();
        foreach ($this->registeredRelativeFilePaths[$bundleDirPath] as $value) {
            $yamlLoader->setRelativeFilePath($value);
            if (!$yamlLoader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $timestamp)) {
                return false;
            }
        }

        return true;
    }

    protected function getRelativeFilePath(string $fileName): string
    {
        return $this->relativeFolderPath . DIRECTORY_SEPARATOR . ltrim($fileName, DIRECTORY_SEPARATOR);
    }

    private function getYamlFileLoader(): YamlCumulativeFileLoader
    {
        if (null === $this->fileLoader) {
            $this->fileLoader = new YamlCumulativeFileLoader($this->relativeFolderPath);
        }
        return $this->fileLoader;
    }

    private function getBundleResourceDirPath(string $bundleAppDir, string $bundleDir): string
    {
        $path = '';
        if (is_dir($bundleAppDir)) {
            $path = rtrim($bundleAppDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->relativeFolderPath;
        }
        if (!$path && is_dir($bundleDir)) {
            $path = rtrim($bundleDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->relativeFolderPath;
        }

        return $path;
    }

    private function getFileResourceFinder(string $realPath): Finder
    {
        $finder = new Finder();

        return $finder->depth($this->depth)
            ->name($this->fileNamePattern)
            ->in($realPath)
            ->sortByName();
    }
}
