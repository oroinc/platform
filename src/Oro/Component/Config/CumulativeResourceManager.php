<?php

namespace Oro\Component\Config;

/**
 * Represents a state for cumulative resource loaders.
 */
final class CumulativeResourceManager
{
    private static ?CumulativeResourceManager $instance = null;
    /** @var callable|null */
    private $initializer;
    private ?string $appRootDir = null;
    private array $bundles = [];
    private array $bundleDirs = [];
    private array $bundleAppDirs = [];
    private array $dirs = [];

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
        $this->initializer = null;
        $this->appRootDir = null;
        $this->bundles = [];
        $this->bundleDirs = [];
        $this->bundleAppDirs = [];
        $this->dirs = [];

        return $this;
    }

    /**
     * Sets a function that should be called to initialize this manager.
     */
    public function setInitializer(callable $initializer): CumulativeResourceManager
    {
        $this->initializer = $initializer;

        return $this;
    }

    /**
     * Gets a list of available bundles with Application like a virtual bundle.
     *
     * @return array [bundle name => bundle class, ...]
     */
    public function getBundles(): array
    {
        $this->ensureInitialized();

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
        $this->ensureInitialized();

        $this->bundles = $bundles;

        return $this;
    }

    /**
     * Gets the application root directory.
     */
    public function getAppRootDir(): ?string
    {
        $this->ensureInitialized();

        return $this->appRootDir;
    }

    /**
     * Sets the application root directory.
     */
    public function setAppRootDir(?string $appRootDir): CumulativeResourceManager
    {
        $this->ensureInitialized();

        $this->appRootDir = $appRootDir;

        return $this;
    }

    /**
     * Gets a directory for the given bundle.
     */
    public function getBundleDir(string $bundleClass): string
    {
        $this->ensureInitialized();

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
        $this->ensureInitialized();

        if (isset($this->bundleAppDirs[$bundleName])) {
            return $this->bundleAppDirs[$bundleName];
        }

        $bundleAppDir = $this->appRootDir && $this->isDirectory($this->appRootDir)
            ? $this->appRootDir . '/Resources/' . $bundleName
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

        $this->ensureInitialized();

        return $this->isDirectory($path);
    }

    private function isDirectory(string $path): bool
    {
        if (isset($this->dirs[$path])) {
            return $this->dirs[$path];
        }

        $isDir = \is_dir($path);
        $this->dirs[$path] = $isDir;

        return $isDir;
    }

    private function ensureInitialized(): void
    {
        if (null !== $this->initializer) {
            $initializer = $this->initializer;
            $this->initializer = null;
            $initializer($this);
        }
    }
}
