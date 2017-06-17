<?php

namespace Oro\Component\PhpUtils;

/**
 * A simple and fast implementation of the class loader
 * that can be used to map one namespace to one path.
 */
class ClassLoader
{
    /** @var string */
    private $namespacePrefix;

    /** @var string */
    private $path;

    /**
     * @param string $namespacePrefix
     * @param string $path
     */
    public function __construct($namespacePrefix, $path)
    {
        $this->namespacePrefix = $namespacePrefix;
        $this->path = $path . DIRECTORY_SEPARATOR;
    }

    /**
     * Registers this class loader on the SPL autoload stack.
     */
    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Removes this class loader from the SPL autoload stack.
     */
    public function unregister()
    {
        spl_autoload_unregister([$this, 'loadClass']);
    }

    /**
     * Loads the given class.
     *
     * @param string $className
     *
     * @return bool TRUE if the class has been successfully loaded, FALSE otherwise.
     */
    public function loadClass($className)
    {
        if (0 !== strpos($className, $this->namespacePrefix)) {
            return false;
        }

        $file = $this->path . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
        if (!is_file($file)) {
            return false;
        }

        require $file;

        return true;
    }
}
