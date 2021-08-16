<?php

namespace Oro\Component\DoctrineUtils\DBAL;

use Oro\Component\PhpUtils\ClassLoader;

/**
 * The utility class that helps to configure the transaction watcher.
 */
final class TransactionWatcherConfigurator
{
    /**
     * The namespace for DBAL connection proxy classes that support the transaction watcher.
     */
    public const CONNECTION_PROXY_NAMESPACE = 'OroDoctrineConnection';

    /**
     * Gets the root directory where the connection proxy should be stored.
     */
    public static function getConnectionProxyRootDir(string $cacheDir): string
    {
        return $cacheDir . DIRECTORY_SEPARATOR . 'oro_entities';
    }

    /**
     * Registers DBAL connection proxy classes that support the transaction watcher.
     */
    public static function registerConnectionProxies(string $cacheDir): void
    {
        $loader = new ClassLoader(
            self::CONNECTION_PROXY_NAMESPACE . '\\',
            self::getConnectionProxyRootDir($cacheDir)
        );
        $loader->register();
    }
}
