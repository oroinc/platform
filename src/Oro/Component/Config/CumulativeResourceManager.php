<?php

namespace Oro\Component\Config;

/**
 * Represents a state for cumulative resource loaders.
 */
class CumulativeResourceManager
{
    /** @var CumulativeResourceManager */
    private static $instance;

    /** @var string */
    private $appRootDir;

    /** @var array */
    private $bundles = [];

    /** @var array */
    private $bundleDirs = [];

    /** @var array */
    private $bundleAppDirs = [];

    /** @var array */
    private $dirs = [];

    /**
     * Gets a singleton instance of the cumulative resource manager.
     */
    public static function getInstance(): CumulativeResourceManager
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * A private constructor to prevent create an instance of this class explicitly.
     */
    private function __construct()
    {
    }

    /**
     * Clears the state of this manager.
     */
    public function clear(): CumulativeResourceManager
    {
        $this->appRootDir = null;
        $this->bundles = [];
        $this->bundleDirs = [];
        $this->bundleAppDirs = [];
        $this->dirs = [];

        return $this;
    }

    /**
     * Gets a list of available bundles with Application like a virtual bundle.
     *
     * @return array [bundle name => bundle class, ...]
     */
    public function getBundles(): array
    {
        return $this->bundles;
    }

    /**
     * Sets a list of available bundles.
     *
     * @param array $bundles [bundle name => bundle class, ...]
     *
     * @return CumulativeResourceManager
     */
    public function setBundles(array $bundles): CumulativeResourceManager
    {
        $this->bundles = $bundles;

        return $this;
    }

    /**
     * Gets the application root directory.
     */
    public function getAppRootDir(): ?string
    {
        return $this->appRootDir;
    }

    /**
     * Sets the application root directory.
     */
    public function setAppRootDir(?string $appRootDir): CumulativeResourceManager
    {
        $this->appRootDir = $appRootDir;

        return $this;
    }

    /**
     * Gets a directory for the given bundle.
     */
    public function getBundleDir(string $bundleClass): string
    {
        if (isset($this->bundleDirs[$bundleClass])) {
            return $this->bundleDirs[$bundleClass];
        }

        $bundleDir = \dirname((new \ReflectionClass($bundleClass))->getFileName());
        $this->bundleDirs[$bundleClass] = $bundleDir;

        return $bundleDir;
    }

    /**
     * Gets a directory for the given bundle in the application directory.
     */
    public function getBundleAppDir(string $bundleName): string
    {
        if (isset($this->bundleAppDirs[$bundleName])) {
            return $this->bundleAppDirs[$bundleName];
        }

        $appRootDir = $this->getAppRootDir();
        $bundleAppDir = $this->appRootDir && $this->isDir($this->appRootDir)
            ? $appRootDir . '/Resources/' . $bundleName
            : '';
        $this->bundleAppDirs[$bundleName] = $bundleAppDir;

        return $bundleAppDir;
    }

    /**
     * Checks if the given path is a directory.
     */
    public function isDir(string $path): bool
    {
        if (!$path) {
            return false;
        }

        if (isset($this->dirs[$path])) {
            return $this->dirs[$path];
        }

        $isDir = \is_dir($path);
        $this->dirs[$path] = $isDir;

        return $isDir;
    }
}
