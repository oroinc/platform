<?php

namespace Oro\Component\PhpUtils;

/**
 * A simple and fast implementation of the class loader
 * that can be used to map one namespace to one file contains all classes from this namespace.
 */
class OneFileClassLoader
{
    private string $namespacePrefix;
    private string $filePath;
    private static array $isFileLoaded = [];

    public function __construct(string $namespacePrefix, string $filePath)
    {
        $this->namespacePrefix = $namespacePrefix;
        $this->filePath = $filePath;
    }

    /**
     * Registers this class loader on the SPL autoload stack.
     */
    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Removes this class loader from the SPL autoload stack.
     */
    public function unregister(): void
    {
        spl_autoload_unregister([$this, 'loadClass']);
    }

    /**
     * Loads the given class.
     */
    public function loadClass(string $className): bool
    {
        if (!str_starts_with($className, $this->namespacePrefix)) {
            return false;
        }

        if (!isset(self::$isFileLoaded[$this->namespacePrefix])) {
            self::$isFileLoaded[$this->namespacePrefix] = true;
            if (false === @include $this->filePath) {
                return false;
            }
        }

        return class_exists($className, false);
    }
}
