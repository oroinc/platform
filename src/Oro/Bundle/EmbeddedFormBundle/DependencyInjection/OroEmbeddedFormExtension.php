<?php

namespace Oro\Bundle\EmbeddedFormBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroEmbeddedFormExtension extends Extension
{
    const SESSION_ID_FIELD_NAME_PARAM = 'oro_embedded_form.session_id_field_name';
    const CSRF_TOKEN_LIFETIME_PARAM   = 'oro_embedded_form.csrf_token_lifetime';

    const CSRF_TOKEN_STORAGE_SERVICE_ID       = 'oro_embedded_form.csrf_token_storage';
    const DEFAULT_CSRF_TOKEN_CACHE_SERVICE_ID = 'oro_embedded_form.csrf_token_cache';
    const DEFAULT_CSRF_TOKEN_CACHE_CLASS      = 'Oro\Bundle\SecurityBundle\Cache\WsseNoncePhpFileCache';
    const DEFAULT_CSRF_TOKEN_CACHE_PATH       = '%kernel.cache_dir%/security/embedded_form';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.yml');
        $loader->load('formatters.yml');
        $loader->load('block_types.yml');

        $container->setParameter(self::SESSION_ID_FIELD_NAME_PARAM, $config[Configuration::SESSION_ID_FIELD_NAME]);
        $container->setParameter(self::CSRF_TOKEN_LIFETIME_PARAM, $config[Configuration::CSRF_TOKEN_LIFETIME]);
        if (!empty($config[Configuration::CSRF_TOKEN_CACHE_SERVICE_ID])) {
            $csrfTokenCacheServiceId = $config[Configuration::CSRF_TOKEN_CACHE_SERVICE_ID];
        } else {
            $csrfTokenCacheServiceId = self::DEFAULT_CSRF_TOKEN_CACHE_SERVICE_ID;
            if (!$container->hasDefinition($csrfTokenCacheServiceId)) {
                $csrfTokenCacheServiceDef = new Definition(
                    self::DEFAULT_CSRF_TOKEN_CACHE_CLASS,
                    [self::DEFAULT_CSRF_TOKEN_CACHE_PATH]
                );
                $csrfTokenCacheServiceDef->setPublic(false);
                $csrfTokenCacheServiceDef->addMethodCall(
                    'setNonceLifeTime',
                    [$container->getParameter(self::CSRF_TOKEN_LIFETIME_PARAM)]
                );
                $container->setDefinition($csrfTokenCacheServiceId, $csrfTokenCacheServiceDef);
            }
        }
        $container
            ->getDefinition(self::CSRF_TOKEN_STORAGE_SERVICE_ID)
            ->replaceArgument(0, new Reference($csrfTokenCacheServiceId));
    }
}
