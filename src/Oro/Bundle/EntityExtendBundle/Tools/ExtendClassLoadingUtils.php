<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Component\PhpUtils\OneFileClassLoader;

/**
 * A set of reusable static methods related to extended entity proxy classes registration.
 */
class ExtendClassLoadingUtils
{
    /**
     * Returns the class namespace of extended entities.
     */
    public static function getEntityNamespace(): string
    {
        return 'Extend\Entity';
    }

    /**
     * Returns base cache directory where all data for extended entities should be located.
     */
    public static function getEntityBaseCacheDir(string $cacheDir): string
    {
        return $cacheDir . DIRECTORY_SEPARATOR . 'oro_entities' . DIRECTORY_SEPARATOR . 'Extend';
    }

    /**
     * Returns directory where extended entities should be located.
     */
    public static function getEntityCacheDir(string $cacheDir): string
    {
        return self::getEntityBaseCacheDir($cacheDir) . DIRECTORY_SEPARATOR . 'Entity';
    }

    /**
     * Returns directory where extended entities should be located.
     */
    public static function getEntityClassesPath(string $cacheDir): string
    {
        return self::getEntityCacheDir($cacheDir) . DIRECTORY_SEPARATOR . 'classes.php';
    }

    /**
     * Checks if a configuration file contains generated classes for enums and custom exists.
     */
    public static function classesExist(string $cacheDir): bool
    {
        return file_exists(self::getEntityClassesPath($cacheDir));
    }

    /**
     * Registers the namespace of extended entities on the SPL autoload stack.
     */
    public static function registerClassLoader(string $cacheDir): void
    {
        $loader = new OneFileClassLoader(
            self::getEntityNamespace() . '\\',
            self::getEntityClassesPath($cacheDir)
        );
        $loader->register();
        $autocompleteLoader = new OneFileClassLoader(
            self::getAutocompleteNamespace() . '\\',
            self::getAutocompleteClassesPath($cacheDir)
        );
        $autocompleteLoader->register();
    }

    /**
     * Checks if directory exists and attempts to create it if it doesn't exist.
     *
     * @throws \RuntimeException if directory creation failed
     */
    public static function ensureDirExists(string $dir): void
    {
        if (!is_dir($dir) && false === @mkdir($dir, 0777, true)) {
            throw new \RuntimeException(sprintf('Could not create cache directory "%s".', $dir));
        }
    }

    public static function getAutocompleteNamespace(): string
    {
        return 'Extend\Entity\Autocomplete';
    }

    public static function getAutocompleteClassesPath(string $cacheDir): string
    {
        return self::getEntityCacheDir($cacheDir) . DIRECTORY_SEPARATOR . 'autocomplete.php';
    }

    public static function getAutocompleteClassName(string $className): string
    {
        $parts = explode('\\', $className);
        $shortClassName = array_pop($parts);
        if (str_starts_with($shortClassName, 'Extend')) {
            $shortClassName = substr($shortClassName, 6);
        }
        $autocompleteShortClassName = array_shift($parts);
        $nameParts = [];
        foreach ($parts as $item) {
            if ($item === 'Bundle' || $item === 'Model') {
                continue;
            }
            if (!isset($nameParts[$item])) {
                $nameParts[$item] = true;
                $autocompleteShortClassName .= $item . '_';
            }
        }

        return $autocompleteShortClassName . $shortClassName;
    }
}
