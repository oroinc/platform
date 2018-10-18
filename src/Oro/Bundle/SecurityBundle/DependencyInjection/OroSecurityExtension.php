<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroSecurityExtension extends Extension implements PrependExtensionInterface
{
    const DEFAULT_WSSE_NONCE_CACHE_SERVICE_ID = 'oro_security.wsse_nonce_cache';
    const DEFAULT_WSSE_NONCE_CACHE_CLASS = 'Oro\Bundle\SecurityBundle\Cache\WsseNoncePhpFileCache';
    const DEFAULT_WSSE_NONCE_CACHE_PATH = '%kernel.cache_dir%/security/nonces';
    const ACLS_CONFIG_ROOT_NODE = 'acls';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('layouts.yml');
        $loader->load('ownership.yml');
        $loader->load('services.yml');
        $loader->load('commands.yml');

        if ($container->getParameter('kernel.debug')) {
            $loader->load('debug.yml');
        }

        $this->addClassesToCompile(['Oro\Bundle\SecurityBundle\Http\Firewall\ContextListener']);

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader = new Loader\YamlFileLoader(
                $container,
                new FileLocator(__DIR__ . '/../Tests/Functional/Environment')
            );
            $loader->load('services.yml');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container instanceof ExtendedContainerBuilder) {
            $this->setupWsseNonceCache($container);
        }
    }

    /**
     * @return CumulativeConfigLoader
     */
    public static function getAclConfigLoader()
    {
        return new CumulativeConfigLoader(
            'oro_acl_config',
            new YamlCumulativeFileLoader('Resources/config/oro/acls.yml')
        );
    }

    /**
     * Sets default implementation of the cache for WSSE nonces if a custom implementation is not specified
     *
     * @param ExtendedContainerBuilder $container
     */
    protected function setupWsseNonceCache(ExtendedContainerBuilder $container)
    {
        $securityConfig = $container->getExtensionConfig('security');
        $hasSecurityConfigChanges = false;
        $wsseLifetime = 0;

        if (isset($securityConfig[0]['firewalls'])) {
            $securityFirewalls = $securityConfig[0]['firewalls'];
            foreach ($securityFirewalls as $name => $config) {
                if (!isset($config['wsse'])) {
                    continue;
                }
                if (!isset($config['wsse']['nonce_cache_service_id'])) {
                    $hasSecurityConfigChanges = true;
                    $securityConfig[0]['firewalls'][$name]['wsse']['nonce_cache_service_id'] =
                        self::DEFAULT_WSSE_NONCE_CACHE_SERVICE_ID;
                }
                if (isset($config['wsse']['lifetime'])
                    && (
                        $wsseLifetime == 0
                        || $wsseLifetime > $config['wsse']['lifetime']
                    )
                ) {
                    $wsseLifetime = $config['wsse']['lifetime'];
                }
            }
        }

        if ($hasSecurityConfigChanges) {
            $container->setExtensionConfig('security', $securityConfig);
            if (!$container->hasDefinition(self::DEFAULT_WSSE_NONCE_CACHE_SERVICE_ID)) {
                $cacheServiceDef = new Definition(
                    self::DEFAULT_WSSE_NONCE_CACHE_CLASS,
                    [self::DEFAULT_WSSE_NONCE_CACHE_PATH]
                );
                if ($wsseLifetime) {
                    $cacheServiceDef->addMethodCall('setNonceLifeTime', [$wsseLifetime]);
                }
                $container->setDefinition(
                    self::DEFAULT_WSSE_NONCE_CACHE_SERVICE_ID,
                    $cacheServiceDef
                );
            }
        }
    }
}
