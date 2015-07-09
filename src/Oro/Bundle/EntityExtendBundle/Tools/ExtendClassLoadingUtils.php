<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

class ExtendClassLoadingUtils
{
    /**
     * Returns base cache directory where all data for extended entities should be located.
     *
     * @param string $cacheDir
     * @return string
     */
    public static function getEntityBaseCacheDir($cacheDir)
    {
        return $cacheDir . '/oro_entities/Extend';
    }

    /**
     * Returns directory where extended entities should be located.
     *
     * @param string $cacheDir
     * @return string
     */
    public static function getEntityCacheDir($cacheDir)
    {
        return $cacheDir . '/oro_entities/Extend/Entity';
    }

    /**
     * Returns a path of a configuration file contains class aliases for extended entities.
     *
     * @param string $cacheDir
     * @return string
     */
    public static function getAliasesPath($cacheDir)
    {
        return self::getEntityCacheDir($cacheDir) . '/alias.data';
    }

    /**
     * Registers the extended entity namespace in the autoloader.
     *
     * @param string $cacheDir
     */
    public static function registerClassLoader($cacheDir)
    {
        // we have to use a loader that extends Doctrine's ClassLoader here rather than
        // Symfony's UniversalClassLoader because in other case Doctrine cannot find our proxies
        // if a class name does not conform Doctrine's conventions for entity class names,
        // for example if a class name contains underscore characters
        // the problem is in Doctrine\Common\ClassLoader::classExists; this method known nothing
        // about Symfony's UniversalClassLoader
        $loader = new ExtendClassLoader('Extend\Entity', $cacheDir . '/oro_entities');
        $loader->register();
    }

    /**
     * Sets class aliases for extended entities.
     *
     * @param string $cacheDir
     */
    public static function setAliases($cacheDir)
    {
        $aliases = self::getAliases($cacheDir);
        foreach ($aliases as $className => $alias) {
            if (class_exists($className) && !class_exists($alias, false)) {
                class_alias($className, $alias);
            }
        }
    }

    /**
     * Gets class aliases for extended entities.
     *
     * @param string $cacheDir
     * @return array
     */
    public static function getAliases($cacheDir)
    {
        $aliasesPath = self::getAliasesPath($cacheDir);
        if (file_exists($aliasesPath)) {
            $aliases = unserialize(
                file_get_contents($aliasesPath, FILE_USE_INCLUDE_PATH)
            );
            if (is_array($aliases)) {
                return $aliases;
            }
        }

        return [];
    }

    /**
     * Checks if directory exists and attempts to create it if it doesn't exist.
     *
     * @param string $dir
     *
     * @throws \RuntimeException if directory creation failed
     */
    public static function ensureDirExists($dir)
    {
        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true)) {
                throw new \RuntimeException(sprintf('Could not create cache directory "%s".', $dir));
            }
        }
    }
}
