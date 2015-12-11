<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\UIBundle\Tools\ArrayUtils;

class OroPlatformExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_app_config',
            new YamlCumulativeFileLoader('Resources/config/oro/app.yml')
        );

        // original security config
        $securityConfig = null;
        $securityModified = false;
        if ($container->hasExtension('security')) {
            $securityConfig = $container->getExtensionConfig('security');
        }

        $resources    = $configLoader->load();
        $extensions   = $container->getExtensions();
        foreach ($resources as $resource) {
            foreach ($resource->data as $name => $config) {
                if (!empty($extensions[$name])) {
                    if ($name === 'security') {
                        $this->mergeConfigIntoOne($container, $name, $config);
                        $securityModified = true;
                    } else {
                        $container->prependExtensionConfig($name, $config);
                    }
                }
            }
        }

        // original security config has highest priority
        if ($securityConfig && $securityModified) {
            $this->mergeConfigIntoOne($container, 'security', reset($securityConfig));
        }

        $this->preparePostgreSql($container);
    }

    /**
     * Enable ATTR_EMULATE_PREPARES for PostgreSQL connections to avoid https://bugs.php.net/bug.php?id=36652
     *
     * @param ContainerBuilder $container
     */
    public function preparePostgreSql(ContainerBuilder $container)
    {
        $dbDriver = $container->getParameter('database_driver');
        if ($dbDriver === DatabaseDriverInterface::DRIVER_POSTGRESQL) {
            $doctrineConfig = $container->getExtensionConfig('doctrine');
            $doctrineConnectionOptions = [];
            foreach ($doctrineConfig as $config) {
                if (isset($config['dbal']['connections'])) {
                    foreach (array_keys($config['dbal']['connections']) as $connectionName) {
                        // Enable ATTR_EMULATE_PREPARES for PostgreSQL
                        $doctrineConnectionOptions['dbal']['connections'][$connectionName]['options'] = [
                            \PDO::ATTR_EMULATE_PREPARES => true
                        ];
                        // Add support of "oid" and "name" Db types for EnterpriseDB
                        $doctrineConnectionOptions['dbal']['connections'][$connectionName]['mapping_types'] = [
                            'oid' => 'integer',
                            'name' => 'string'
                        ];
                    }
                }
            }
            $container->prependExtensionConfig('doctrine', $doctrineConnectionOptions);
        }
    }

    /**
     * Merge configuration into one config
     *
     * @param ContainerBuilder $container
     * @param string $name
     * @param array $config
     *
     * @throws \RuntimeException
     */
    private function mergeConfigIntoOne(ContainerBuilder $container, $name, array $config = [])
    {
        if (!$container instanceof ExtendedContainerBuilder) {
            throw new \RuntimeException(
                sprintf(
                    '%s is expected to be passed into OroPlatformExtension',
                    'Oro\Component\DependencyInjection\ExtendedContainerBuilder'
                )
            );
        }

        $originalConfig = $container->getExtensionConfig($name);
        if (!count($originalConfig)) {
            $originalConfig[] = array();
        }

        $mergedConfig = ArrayUtils::arrayMergeRecursiveDistinct($originalConfig[0], $config);
        $originalConfig[0] = $mergedConfig;

        $container->setExtensionConfig($name, $originalConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.yml');
        $loader->load('doctrine.yml');
        $loader->load('session.yml');
    }
}
