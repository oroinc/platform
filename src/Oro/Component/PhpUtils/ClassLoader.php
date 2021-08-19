<?php

namespace Oro\Component\PhpUtils;

/**
 * A simple and fast implementation of the class loader
 * that can be used to map one namespace to one path.
 */
class ClassLoader
{
    private string $namespacePrefix;
    private string $path;

    public function __construct(string $namespacePrefix, string $path)
    {
        $this->namespacePrefix = $namespacePrefix;
        $this->path = $path . DIRECTORY_SEPARATOR;
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

        $file = $this->path . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
        if (false === @include $file) {
            return false;
        }

        return true;
    }
}
