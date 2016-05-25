<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

use Oro\Bundle\SecurityBundle\Annotation\Loader\AclAnnotationCumulativeResourceLoader;

class OroSecurityExtension extends Extension implements PrependExtensionInterface
{
    const DEFAULT_WSSE_NONCE_CACHE_SERVICE_ID = 'oro_security.wsse_nonce_cache';
    const DEFAULT_WSSE_NONCE_CACHE_CLASS = 'Oro\Bundle\SecurityBundle\Cache\WsseNoncePhpFileCache';
    const DEFAULT_WSSE_NONCE_CACHE_PATH = '%kernel.cache_dir%/security/nonces';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        self::getAclConfigLoader()->registerResources($container);
        self::getAclAnnotationLoader()->registerResources($container);

        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('layouts.yml');
        $loader->load('ownership.yml');
        $loader->load('services.yml');

        $this->addClassesToCompile(['Oro\Bundle\SecurityBundle\Http\Firewall\ContextListener']);
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
            new YamlCumulativeFileLoader('Resources/config/acl.yml')
        );
    }

    /**
     * @return CumulativeConfigLoader
     */
    public static function getAclAnnotationLoader()
    {
        return new CumulativeConfigLoader(
            'oro_acl_annotation',
            new AclAnnotationCumulativeResourceLoader(['Controller'])
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
