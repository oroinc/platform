<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\JmsSerializerPass;
use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroPlatformExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        $this->loadAppConfigsFromBundles($container);
        $this->preparePostgreSql($container);
        $this->configureJmsSerializer($container);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function loadAppConfigsFromBundles(ContainerBuilder $container): void
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_app_config',
            new YamlCumulativeFileLoader('Resources/config/oro/app.yml')
        );

        // original security config
        $securityConfig = null;
        if ($container->hasExtension('security')) {
            $securityConfig = $container->getExtensionConfig('security');
        }

        $securityModified = false;
        $securityConfigs = [];

        $extensions = $container->getExtensions();

        // bundles that are loaded later should be able to override configuration of bundles loaded before
        $resources = array_reverse($configLoader->load(new ContainerBuilderAdapter($container)));
        foreach ($resources as $resource) {
            foreach ($resource->data as $name => $config) {
                if ('services' === $name) {
                    $loader = new Loader\YamlFileLoader(
                        $container,
                        new FileLocator(rtrim($resource->path, 'app.yml'))
                    );
                    $loader->load('app.yml');
                    continue;
                }

                if (empty($extensions[$name])) {
                    continue;
                }

                if ('security' === $name) {
                    $securityConfigs[] = $config;
                    $securityModified = true;
                } else {
                    $container->prependExtensionConfig($name, $config);
                }
            }
        }

        if ($securityModified) {
            $securityConfigs = array_reverse($securityConfigs);
            foreach ($securityConfigs as $config) {
                $this->mergeConfigIntoOne($container, 'security', $config);
            }
        }

        // original security config has highest priority
        if ($securityConfig && $securityModified) {
            $this->mergeConfigIntoOne($container, 'security', reset($securityConfig));
        }
    }

    /**
     * Enable ATTR_EMULATE_PREPARES for PostgreSQL connections to avoid https://bugs.php.net/bug.php?id=36652
     */
    private function preparePostgreSql(ContainerBuilder $container): void
    {
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
                        'oid'  => 'integer',
                        'name' => 'string'
                    ];
                }
            }
        }
        $container->prependExtensionConfig('doctrine', $doctrineConnectionOptions);
    }

    /**
     * Configures JMS Serializer if JMSSerializerBundle is installed.
     */
    private function configureJmsSerializer(ContainerBuilder $container): void
    {
        if ($container->hasExtension('jms_serializer')) {
            $container->prependExtensionConfig('jms_serializer', [
                'metadata' => [
                    'cache' => JmsSerializerPass::JMS_SERIALIZER_CACHE_ADAPTER_SERVICE_ID
                ]
            ]);
        }
    }

    private function mergeConfigIntoOne(ContainerBuilder $container, string $name, array $config = []): void
    {
        if (!$container instanceof ExtendedContainerBuilder) {
            throw new \RuntimeException(sprintf(
                '%s is expected to be passed into OroPlatformExtension',
                ExtendedContainerBuilder::class
            ));
        }

        $originalConfig = $container->getExtensionConfig($name);
        if (!\count($originalConfig)) {
            $originalConfig[] = [];
        }

        $mergedConfig = ArrayUtil::arrayMergeRecursiveDistinct($originalConfig[0], $config);
        $originalConfig[0] = $mergedConfig;

        $container->setExtensionConfig($name, $originalConfig);
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('doctrine.yml');
        $loader->load('session.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
        $loader->load('mq_topics.yml');
        $loader->load('mq_processors.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }
    }
}
