<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Checks whether an additional unnamed DBAL configuration exists,
 * and if so, adds it for all DBAL connections that have the same driver, DB name, host and port
 * as a default DBAL connection.
 * This is required because DoctrineBundle adds unnamed DBAL configuration only to a default DBAL connection.
 * @see \Doctrine\Bundle\DoctrineBundle\DependencyInjection\Configuration::addDbalSection
 * For example, if the following configuration is added to an application configuration file:
 * <code>
 *  doctrine:
 *      dbal:
 *          charset: utf8mb4
 *          default_table_options:
 *              charset: utf8mb4
 *              collate: utf8mb4_unicode_ci
 * </code>
 * and there is DBAL connection named "another" that has the same driver, DB name, host and port
 * as a default DBAL connection, the following additional configuration will be added:
 * <code>
 *  doctrine:
 *      dbal:
 *          connections:
 *              another:
 *                  charset: utf8mb4
 *                  default_table_options:
 *                      charset: utf8mb4
 *                      collate: utf8mb4_unicode_ci
 * </code>
 * this additional configuration will be added just before the unnamed DBAL configuration
 * to be able to override any of the added options for a specific DBAL connection.
 */
class DbalConfigurationLoader
{
    /**
     * @param ContainerBuilder $container
     */
    public static function load(ContainerBuilder $container): void
    {
        if (!$container instanceof ExtendedContainerBuilder) {
            return;
        }
        $doctrineConfig = $container->getExtensionConfig('doctrine');
        $commonConfig = self::getCommonDbalConfig($doctrineConfig);
        if (empty($commonConfig)) {
            return;
        }
        $defaultConnectionName = self::getDefaultDbalConnectionName($doctrineConfig);
        if (!$defaultConnectionName) {
            return;
        }
        $defaultConnectionKey = self::getDbalConnectionKey($doctrineConfig, $defaultConnectionName);
        if (!$defaultConnectionKey) {
            return;
        }
        $indexOffset = 0;
        $connectionNames = self::getDbalConnectionNames($doctrineConfig);
        foreach ($commonConfig as $index => $config) {
            $additionalConfig = [];
            foreach ($connectionNames as $connectionName) {
                if ($connectionName !== $defaultConnectionName
                    && self::getDbalConnectionKey($doctrineConfig, $connectionName) === $defaultConnectionKey
                ) {
                    $additionalConfig[$connectionName] = $config;
                }
            }
            if (!empty($additionalConfig)) {
                $doctrineConfig = \array_merge(
                    \array_slice($doctrineConfig, 0, $index + $indexOffset),
                    [['dbal' => ['connections' => $additionalConfig]]],
                    \array_slice($doctrineConfig, $index + $indexOffset)
                );
                $indexOffset++;
            }
        }
        $container->setExtensionConfig('doctrine', $doctrineConfig);
    }

    /**
     * @param array $doctrineConfig
     *
     * @return array
     */
    private static function getCommonDbalConfig(array $doctrineConfig): array
    {
        /**
         * keys that should not be rewritten to the connection config
         * @see \Doctrine\Bundle\DoctrineBundle\DependencyInjection\Configuration::addDbalSection
         */
        $excludedKeys = ['default_connection', 'types', 'type'];

        $commonConfig = [];
        foreach ($doctrineConfig as $index => $config) {
            if (\is_array($config) && self::hasCommonDbalConfig($config)) {
                $dbal = $config['dbal'];
                foreach ($excludedKeys as $key) {
                    unset($dbal[$key]);
                }
                if (!empty($dbal)) {
                    $commonConfig[$index] = $dbal;
                }
            }
        }

        return $commonConfig;
    }

    /**
     * @param array $config
     *
     * @return bool
     */
    private static function hasCommonDbalConfig(array $config): bool
    {
        /**
         * @see \Doctrine\Bundle\DoctrineBundle\DependencyInjection\Configuration::addDbalSection
         */
        return
            !empty($config['dbal'])
            && \is_array($config['dbal'])
            && !\array_key_exists('connections', $config['dbal'])
            && !\array_key_exists('connection', $config['dbal']);
    }

    /**
     * @param array $doctrineConfig
     *
     * @return string|null
     */
    private static function getDefaultDbalConnectionName(array $doctrineConfig): ?string
    {
        $defaultConnectionName = null;
        foreach ($doctrineConfig as $config) {
            if (\is_array($config) && !empty($config['dbal']['default_connection'])) {
                $defaultConnectionName = $config['dbal']['default_connection'];
            }
        }

        return $defaultConnectionName;
    }

    /**
     * @param array $doctrineConfig
     *
     * @return string[]
     */
    private static function getDbalConnectionNames(array $doctrineConfig): array
    {
        $connectionNames = [];
        foreach ($doctrineConfig as $config) {
            if (\is_array($config) && !empty($config['dbal']) && \is_array($config['dbal'])) {
                $connections = self::getDbalConnections($config['dbal']);
                if (!empty($connections)) {
                    $connectionNames = \array_merge($connectionNames, \array_keys($connections));
                }
            }
        }

        return \array_unique($connectionNames);
    }

    /**
     * @param array  $doctrineConfig
     * @param string $connectionName
     *
     * @return string|null
     */
    private static function getDbalConnectionKey(array $doctrineConfig, string $connectionName): ?string
    {
        $driver = '';
        $dbname = '';
        $host = '';
        $port = '';
        foreach ($doctrineConfig as $config) {
            if (\is_array($config) && !empty($config['dbal']) && \is_array($config['dbal'])) {
                $connections = self::getDbalConnections($config['dbal']);
                if (!empty($connections) && !empty($connections[$connectionName])) {
                    $connection = $connections[$connectionName];
                    if (\array_key_exists('driver', $connection)) {
                        $driver = $connection['driver'];
                    }
                    if (\array_key_exists('dbname', $connection)) {
                        $dbname = $connection['dbname'];
                    }
                    if (\array_key_exists('host', $connection)) {
                        $host = $connection['host'];
                    }
                    if (\array_key_exists('port', $connection)) {
                        $port = $connection['port'];
                    }
                }
            }
        }

        if (!$driver || !$dbname) {
            return null;
        }

        return \sprintf('%s|%s|%s|%s', $driver, $dbname, $host, $port);
    }

    /**
     * @param array $dbalConfig
     *
     * @return array|null
     */
    private static function getDbalConnections(array $dbalConfig): ?array
    {
        if (!empty($dbalConfig['connections']) && \is_array($dbalConfig['connections'])) {
            return $dbalConfig['connections'];
        }
        if (!empty($dbalConfig['connection']) && \is_array($dbalConfig['connection'])) {
            return $dbalConfig['connection'];
        }

        return null;
    }
}
