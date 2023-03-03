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
        return $cacheDir . DIRECTORY_SEPARATOR . 'oro_entities' . DIRECTORY_SEPARATOR. 'Extend';
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
     * Returns a path of a configuration file contains class aliases for extended entities.
     */
    public static function getAliasesPath(string $cacheDir): string
    {
        return self::getEntityCacheDir($cacheDir) . DIRECTORY_SEPARATOR . 'aliases.php';
    }

    /**
     * Checks if a configuration file contains class aliases for extended entities exists.
     */
    public static function aliasesExist(string $cacheDir): bool
    {
        return file_exists(self::getAliasesPath($cacheDir));
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
    }

    /**
     * Gets class aliases for extended entities.
     */
    public static function getAliases(string $cacheDir): array
    {
        $aliases = @include self::getAliasesPath($cacheDir);
        if (false === $aliases || !\is_array($aliases)) {
            $aliases = [];
        }

        return $aliases;
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
}
