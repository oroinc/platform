<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Sets "database_charset" parameter if it is not set yet. The charset is extracted
 * from a default DBAL connection or set to utf8 if the charset of the default connection is unknown.
 * Also checks whether an additional unnamed DBAL configuration exists,
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
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DbalConfigurationLoader
{
    const DATABASE_CHARSET   = 'database_charset';
    const DOCTRINE           = 'doctrine';
    const DBAL               = 'dbal';
    const DEFAULT_CONNECTION = 'default_connection';
    const CONNECTIONS        = 'connections';
    const CONNECTION         = 'connection';
    const DRIVER             = 'driver';
    const DBNAME             = 'dbname';
    const HOST               = 'host';
    const PORT               = 'port';
    const CHARSET            = 'charset';

    /**
     * @param ContainerBuilder $container
     */
    public static function load(ContainerBuilder $container)
    {
        $doctrineConfig = $container->getExtensionConfig(self::DOCTRINE);
        $commonConfig = self::getCommonDbalConfig($doctrineConfig);
        $defaultConnectionName = self::getDefaultDbalConnectionName($doctrineConfig);
        $defaultConnectionKey = $defaultConnectionName
            ? self::getDbalConnectionKey($doctrineConfig, $defaultConnectionName)
            : null;

        if (!$container->hasParameter(self::DATABASE_CHARSET)) {
            $container->setParameter(
                self::DATABASE_CHARSET,
                self::getDefaultCharset($doctrineConfig, $commonConfig, $defaultConnectionName)
            );
        }

        if ($container instanceof ExtendedContainerBuilder
            && !empty($commonConfig)
            && $defaultConnectionName
            && $defaultConnectionKey
        ) {
            $doctrineConfig = self::applyCommonConfig(
                $doctrineConfig,
                $commonConfig,
                $defaultConnectionName,
                $defaultConnectionKey
            );
            $container->setExtensionConfig(self::DOCTRINE, $doctrineConfig);
        }
    }

    /**
     * @param array       $doctrineConfig
     * @param array       $commonConfig
     * @param string|null $defaultConnectionName
     *
     * @return string
     */
    private static function getDefaultCharset(
        $doctrineConfig,
        $commonConfig,
        $defaultConnectionName
    ) {
        $charset = self::getCommonOptionValue($commonConfig, self::CHARSET);
        if ($defaultConnectionName && (!$charset || self::isParameter($charset))) {
            $charset = self::getDbalConnectionOptionValue($doctrineConfig, $defaultConnectionName, self::CHARSET);
        }
        if (self::isParameter($charset)) {
            $charset = null;
        }

        return $charset ?: 'utf8';
    }

    /**
     * @param mixed $optionValue
     *
     * @return bool
     */
    private static function isParameter($optionValue)
    {
        return
            is_string($optionValue)
            && strpos($optionValue, '%') === 0;
    }

    /**
     * @param array  $doctrineConfig
     * @param array  $commonConfig
     * @param string $defaultConnectionName
     * @param string $defaultConnectionKey
     *
     * @return array
     */
    private static function applyCommonConfig(
        $doctrineConfig,
        $commonConfig,
        $defaultConnectionName,
        $defaultConnectionKey
    ) {
        $indexOffset = 0;
        $connectionNames = self::getDbalConnectionNames($doctrineConfig);
        foreach ($commonConfig as $index => $config) {
            $additionalConfig = self::getAdditionalConfig(
                $doctrineConfig,
                $config,
                $index,
                $connectionNames,
                $defaultConnectionName,
                $defaultConnectionKey
            );
            if (!empty($additionalConfig)) {
                $doctrineConfig = self::insertConfigAt(
                    $doctrineConfig,
                    [self::DBAL => [self::CONNECTIONS => $additionalConfig]],
                    $index + $indexOffset
                );
                $indexOffset++;
            }
        }

        return $doctrineConfig;
    }

    /**
     * @param array  $doctrineConfig
     * @param array  $config
     * @param int    $index
     * @param array  $connectionNames
     * @param string $defaultConnectionName
     * @param string $defaultConnectionKey
     *
     * @return array
     */
    private static function getAdditionalConfig(
        $doctrineConfig,
        $config,
        $index,
        $connectionNames,
        $defaultConnectionName,
        $defaultConnectionKey
    ) {
        $additionalConfig = [];
        foreach ($connectionNames as $connectionName) {
            if ((-1 === $index || $connectionName !== $defaultConnectionName)
                && self::getDbalConnectionKey($doctrineConfig, $connectionName) === $defaultConnectionKey
            ) {
                $connectionConfig = [];
                foreach ($config as $key => $value) {
                    if (!self::hasDbalConnectionOption($doctrineConfig, $connectionName, $key, $index)) {
                        $connectionConfig[$key] = $value;
                    }
                }
                $additionalConfig[$connectionName] = $connectionConfig;
            }
        }

        return $additionalConfig;
    }

    /**
     * @param array  $doctrineConfig
     * @param string $connectionName
     * @param string $optionName
     * @param int    $lastIndex
     *
     * @return bool
     */
    private static function hasDbalConnectionOption(
        $doctrineConfig,
        $connectionName,
        $optionName,
        $lastIndex
    ) {
        $hasOption = false;
        foreach ($doctrineConfig as $index => $config) {
            if ($index > $lastIndex) {
                $hasOption = true;
                break;
            }
            if (is_array($config) && isset($config[self::DBAL][self::CONNECTIONS][$connectionName])) {
                $connection = $config[self::DBAL][self::CONNECTIONS][$connectionName];
                if (is_array($connection) && array_key_exists($optionName, $connection)) {
                    $hasOption = true;
                    break;
                }
            }
        }

        return $hasOption;
    }

    /**
     * @param array  $doctrineConfig
     * @param string $connectionName
     * @param string $optionName
     *
     * @return mixed
     */
    private static function getDbalConnectionOptionValue(
        $doctrineConfig,
        $connectionName,
        $optionName
    ) {
        $optionValue = null;
        foreach ($doctrineConfig as $index => $config) {
            if (is_array($config) && isset($config[self::DBAL][self::CONNECTIONS][$connectionName])) {
                $connection = $config[self::DBAL][self::CONNECTIONS][$connectionName];
                if (is_array($connection) && array_key_exists($optionName, $connection)) {
                    $optionValue = $connection[$optionName];
                }
            }
        }

        return $optionValue;
    }

    /**
     * @param array  $commonConfig
     * @param string $optionName
     *
     * @return mixed
     */
    private static function getCommonOptionValue($commonConfig, $optionName)
    {
        $optionValue = null;
        foreach ($commonConfig as $config) {
            if (is_array($config) && array_key_exists($optionName, $config)) {
                $optionValue = $config[$optionName];
            }
        }

        return $optionValue;
    }

    /**
     * @param array $doctrineConfig
     * @param array $config
     * @param int   $index
     *
     * @return array
     */
    private static function insertConfigAt($doctrineConfig, $config, $index)
    {
        if (-1 === $index) {
            return array_merge([$config], $doctrineConfig);
        }

        return array_merge(
            array_slice($doctrineConfig, 0, $index),
            [$config],
            array_slice($doctrineConfig, $index)
        );
    }

    /**
     * @param array $doctrineConfig
     *
     * @return array
     */
    private static function getCommonDbalConfig($doctrineConfig)
    {
        /**
         * keys that should not be rewritten to the connection config
         * @see \Doctrine\Bundle\DoctrineBundle\DependencyInjection\Configuration::addDbalSection
         */
        $excludedKeys = [self::DEFAULT_CONNECTION, 'types', 'type'];

        $commonConfig = [];
        foreach ($doctrineConfig as $index => $config) {
            if (is_array($config) && self::hasCommonDbalConfig($config)) {
                $dbal = $config[self::DBAL];
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
    private static function hasCommonDbalConfig($config)
    {
        /**
         * @see \Doctrine\Bundle\DoctrineBundle\DependencyInjection\Configuration::addDbalSection
         */
        return
            !empty($config[self::DBAL])
            && is_array($config[self::DBAL])
            && !array_key_exists(self::CONNECTIONS, $config[self::DBAL])
            && !array_key_exists(self::CONNECTION, $config[self::DBAL]);
    }

    /**
     * @param array $doctrineConfig
     *
     * @return string|null
     */
    private static function getDefaultDbalConnectionName($doctrineConfig)
    {
        $defaultConnectionName = null;
        foreach ($doctrineConfig as $config) {
            if (is_array($config) && !empty($config[self::DBAL][self::DEFAULT_CONNECTION])) {
                $defaultConnectionName = $config[self::DBAL][self::DEFAULT_CONNECTION];
            }
        }

        return $defaultConnectionName;
    }

    /**
     * @param array $doctrineConfig
     *
     * @return string[]
     */
    private static function getDbalConnectionNames($doctrineConfig)
    {
        $connectionNames = [];
        foreach ($doctrineConfig as $config) {
            if (is_array($config) && !empty($config[self::DBAL]) && is_array($config[self::DBAL])) {
                $connections = self::getDbalConnections($config[self::DBAL]);
                if (!empty($connections)) {
                    $connectionNames = array_merge($connectionNames, array_keys($connections));
                }
            }
        }

        return array_unique($connectionNames);
    }

    /**
     * @param array  $doctrineConfig
     * @param string $connectionName
     *
     * @return string|null
     */
    private static function getDbalConnectionKey($doctrineConfig, $connectionName)
    {
        $driver = '';
        $dbname = '';
        $host = '';
        $port = '';
        foreach ($doctrineConfig as $config) {
            if (is_array($config) && !empty($config[self::DBAL]) && is_array($config[self::DBAL])) {
                $connections = self::getDbalConnections($config[self::DBAL]);
                if (!empty($connections) && !empty($connections[$connectionName])) {
                    $connection = $connections[$connectionName];
                    if (array_key_exists(self::DRIVER, $connection)) {
                        $driver = $connection[self::DRIVER];
                    }
                    if (array_key_exists(self::DBNAME, $connection)) {
                        $dbname = $connection[self::DBNAME];
                    }
                    if (array_key_exists(self::HOST, $connection)) {
                        $host = $connection[self::HOST];
                    }
                    if (array_key_exists(self::PORT, $connection)) {
                        $port = $connection[self::PORT];
                    }
                }
            }
        }

        if (!$driver || !$dbname) {
            return null;
        }

        return sprintf('%s|%s|%s|%s', $driver, $dbname, $host, $port);
    }

    /**
     * @param array $dbalConfig
     *
     * @return array|null
     */
    private static function getDbalConnections($dbalConfig)
    {
        if (!empty($dbalConfig[self::CONNECTIONS]) && is_array($dbalConfig[self::CONNECTIONS])) {
            return $dbalConfig[self::CONNECTIONS];
        }
        if (!empty($dbalConfig[self::CONNECTION]) && is_array($dbalConfig[self::CONNECTION])) {
            return $dbalConfig[self::CONNECTION];
        }

        return null;
    }
}
