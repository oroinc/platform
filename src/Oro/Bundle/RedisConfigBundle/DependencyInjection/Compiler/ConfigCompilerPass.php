<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler;

use Oro\Bundle\RedisConfigBundle\DependencyInjection\RedisEnabledCheckTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Sets the oro_redirect.url_cache_type param value to key_value from storage.
 *
 * @link https://doc.oroinc.com/bundles/commerce/RedirectBundle/#semantic-url-caching
 */
class ConfigCompilerPass implements CompilerPassInterface
{
    use RedisEnabledCheckTrait;

    private const URL_CACHE_TYPE      = 'oro_redirect.url_cache_type';
    private const URL_CACHE_STORAGE   = 'storage';
    private const URL_CACHE_KEY_VALUE = 'key_value';

    public function process(ContainerBuilder $container): void
    {
        if ($this->isRedisEnabledForCache($container)
            && $container->hasParameter(self::URL_CACHE_TYPE)
            && $container->getParameter(self::URL_CACHE_TYPE) === self::URL_CACHE_STORAGE
        ) {
            $container->setParameter(self::URL_CACHE_TYPE, self::URL_CACHE_KEY_VALUE);
        }
    }
}
