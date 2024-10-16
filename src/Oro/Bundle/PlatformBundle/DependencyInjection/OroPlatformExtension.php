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
    #[\Override]
    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('web_backend_prefix')) {
            $container->setParameter('web_backend_prefix', '/admin');
        }

        $this->loadAppConfigsFromBundles($container);
        $this->preparePostgreSql($container);
        $this->configureJmsSerializer($container);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
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
        if (isset($securityConfig[0]['access_control'])) {
            $this->throwAccessControlException();
        }

        // check app level security access_control rules
        $accessControlConfigs = [];
        if ($container->hasExtension('oro_security')) {
            $oroSecurityConfigs = $container->getExtensionConfig('oro_security');
            foreach ($oroSecurityConfigs as $conf) {
                if (isset($conf['access_control'])) {
                    $accessControlConfigs[] = $conf['access_control'];
                }
            }
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

                switch ($name) {
                    case 'security':
                        if (isset($config['access_control'])) {
                            $this->throwAccessControlException();
                        }
                        $securityConfigs[] = $config;
                        $securityModified = true;
                        break;
                    case 'oro_security':
                        if (isset($config['access_control'])) {
                            $accessControlConfigs[] = $config['access_control'];
                        }
                        $container->prependExtensionConfig($name, $config);
                        break;
                    default:
                        $container->prependExtensionConfig($name, $config);
                }
            }
        }
        $accessControlConfigs = $this->sortAccessControl($accessControlConfigs);

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

        $this->mergeConfigIntoOne($container, 'security', ['access_control' => $accessControlConfigs]);
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

    #[\Override]
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

    private function throwAccessControlException()
    {
        throw new \LogicException(
            '\'access_control\' configuration is not allowed in traditional security section. Please ' .
            'define the rules in the \'oro_security\' configuration scope.'
        );
    }

    /**
     * Sorts the access_control list considering priority.
     * Missing priority config is considered to be 0.
     * Will sort by priority, but second level sorting will result from bundle loading order.
     *
     * @param array $accessControlConfig
     * @return array|mixed
     */
    private function sortAccessControl(array $accessControlConfig)
    {
        if (!$accessControlConfig) {
            return $accessControlConfig;
        }
        $accessControlConfig = array_reverse($accessControlConfig);
        $finalAccessControlConf = [];
        foreach ($accessControlConfig as $conf) {
            $finalAccessControlConf = ArrayUtil::arrayMergeRecursiveDistinct($finalAccessControlConf, $conf);
        }

        usort($finalAccessControlConf, function ($subConf1, $subConf2) {
            $subConf1['priority'] = $subConf1['priority'] ?? 0;
            $subConf2['priority'] = $subConf2['priority'] ?? 0;

            return $subConf2['priority'] <=> $subConf1['priority'];
        });
        foreach ($finalAccessControlConf as &$tmpConf) {
            unset($tmpConf['priority']);
        }
        unset($tmpConf);

        return $finalAccessControlConf;
    }
}
