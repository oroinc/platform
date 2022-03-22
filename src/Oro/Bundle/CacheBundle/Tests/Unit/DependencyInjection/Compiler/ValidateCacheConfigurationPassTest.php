<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass;
use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\ValidateCacheConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ValidateCacheConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    public function testAllCachesHaveNamespaces()
    {
        $container = new ContainerBuilder();
        $container->register('cache_provider_1')->addTag('cache.pool', ['namespace' => 'namespace1']);
        $container->register('cache_provider_2')->addTag('cache.pool', ['namespace' => 'namespace2']);
        $cacheManagerDef = $container->register(CacheConfigurationPass::MANAGER_SERVICE_KEY);
        $cacheManagerDef->addMethodCall('registerCacheProvider', [new Reference('cache_provider_1')]);
        $cacheManagerDef->addMethodCall('registerCacheProvider', [new Reference('cache_provider_2')]);

        $compiler = new ValidateCacheConfigurationPass();
        $compiler->process($container);
    }

    public function testSomeCacheDoesNotHaveNamespace()
    {
        $container = new ContainerBuilder();
        $container->register('cache_provider_1')->addTag('cache.pool', ['namespace' => 'namespace1']);
        $container->register('cache_provider_2');
        $cacheManagerDef = $container->register(CacheConfigurationPass::MANAGER_SERVICE_KEY);
        $cacheManagerDef->addMethodCall('registerCacheProvider', [new Reference('cache_provider_1')]);
        $cacheManagerDef->addMethodCall('registerCacheProvider', [new Reference('cache_provider_2')]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The namespace for the "cache_provider_2" cache service must be defined.'
            . ' Make sure that the "setNamespace" method call exists in the service definition.'
        );

        $compiler = new ValidateCacheConfigurationPass();
        $compiler->process($container);
    }
}
