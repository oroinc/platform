<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CacheBundle\Adapter\ChainAdapter;
use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass;
use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Oro\Component\Config\Cache\ConfigCacheWarmer;
use Symfony\Component\Cache\Adapter\ChainAdapter as SymfonyChainAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CacheConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    public function testExistingCacheDefinitionsShouldNotBeChanged()
    {
        $cacheDefinition = new Definition(
            FilesystemAdapter::class,
            ['', 0, '%kernel.cache_dir%/oro_data']
        );
        $cacheDefinition->setAbstract(true);

        $container = new ContainerBuilder();
        $container->register(CacheConfigurationPass::MANAGER_SERVICE_KEY);
        $container->setDefinition(CacheConfigurationPass::DATA_CACHE_POOL, $cacheDefinition);

        $compiler = new CacheConfigurationPass();
        $compiler->process($container);

        $this->assertEquals(
            (new Definition(FilesystemAdapter::class, ['', 0, '%kernel.cache_dir%/oro_data']))->setAbstract(true),
            $container->getDefinition(CacheConfigurationPass::DATA_CACHE_POOL)
        );
    }

    public function testAbstractDefinitionWithoutClass()
    {
        $abstractDataCacheDef = new Definition();
        $abstractDataCacheDef->setAbstract(true);

        $container = new ContainerBuilder();
        $container->register(CacheConfigurationPass::MANAGER_SERVICE_KEY);
        $container->setDefinition(CacheConfigurationPass::DATA_CACHE_POOL, $abstractDataCacheDef);

        $compiler = new CacheConfigurationPass();

        $compiler->process($container);
    }

    public function testAbstractDefinitionWithSupportedClass()
    {
        $abstractDataCacheDef = $this->getFilesystemCache();

        $container = new ContainerBuilder();
        $container->register(CacheConfigurationPass::MANAGER_SERVICE_KEY);
        $container->setDefinition(CacheConfigurationPass::DATA_CACHE_POOL, $abstractDataCacheDef);

        $compiler = new CacheConfigurationPass();

        $compiler->process($container);
    }

    public function testDataCacheManagerConfiguration()
    {
        $dataCacheManagerDef = new Definition(OroDataCacheManager::class);
        $dataCacheDef = new ChildDefinition(CacheConfigurationPass::DATA_CACHE_POOL);
        $abstractDataCacheDef = new ChildDefinition(CacheConfigurationPass::DATA_CACHE_POOL);
        $abstractDataCacheDef->setAbstract(true);
        $otherCacheDef = new ChildDefinition('some_abstract_cache');

        $container = new ContainerBuilder();
        $container->setDefinition(CacheConfigurationPass::MANAGER_SERVICE_KEY, $dataCacheManagerDef);
        $container->setDefinition('data_cache', $dataCacheDef);
        $container->setDefinition('abstract_data_cache', $abstractDataCacheDef);
        $container->setDefinition('other_cache', $otherCacheDef);

        $compiler = new CacheConfigurationPass();
        $compiler->process($container);

        $expectedDataCacheManagerDef = new Definition(OroDataCacheManager::class);
        $expectedDataCacheManagerDef->addMethodCall('registerCacheProvider', [new Reference('data_cache')]);
        $this->assertEquals(
            $expectedDataCacheManagerDef,
            $container->getDefinition(CacheConfigurationPass::MANAGER_SERVICE_KEY)
        );
    }

    public function testStaticConfigCacheWarmers()
    {
        $providerDef = new ChildDefinition(CacheConfigurationPass::STATIC_CONFIG_PROVIDER_SERVICE);
        $abstractProviderDef = new ChildDefinition(CacheConfigurationPass::STATIC_CONFIG_PROVIDER_SERVICE);
        $abstractProviderDef->setAbstract(true);
        $providerWithWarmerDef = new ChildDefinition(CacheConfigurationPass::STATIC_CONFIG_PROVIDER_SERVICE);
        $existingWarmerDef = new Definition('TestWarmer');
        $notConfigProviderDef = new ChildDefinition('some_abstract_service');

        $container = new ContainerBuilder();
        $container->register(CacheConfigurationPass::MANAGER_SERVICE_KEY);
        $container->setDefinition('provider', $providerDef);
        $container->setDefinition('abstract_provider', $abstractProviderDef);
        $container->setDefinition('provider_with_warmer', $providerWithWarmerDef);
        $container->setDefinition('not_config_provider', $notConfigProviderDef);
        $container->setDefinition('provider_with_warmer.warmer', $existingWarmerDef);

        $compiler = new CacheConfigurationPass();
        $compiler->process($container);

        $expectedWarmerDef = new Definition(ConfigCacheWarmer::class);
        $expectedWarmerDef
            ->setPublic(false)
            ->setArguments([new Reference('provider')])
            ->addTag('kernel.cache_warmer', ['priority' => 200]);

        $this->assertEquals(
            $expectedWarmerDef,
            $container->getDefinition('provider.warmer')
        );
        $this->assertFalse($container->hasDefinition('abstract_provider.warmer'));
        $this->assertSame(
            $existingWarmerDef,
            $container->getDefinition('provider_with_warmer.warmer')
        );
        $this->assertFalse($container->hasDefinition('not_config_provider.warmer'));
    }

    public function testClassForChainAdapter()
    {
        $dataCachePoolDef = new Definition(SymfonyChainAdapter::class);
        $container = new ContainerBuilder();
        $container->register(CacheConfigurationPass::DATA_CACHE_POOL);
        $container->register(CacheConfigurationPass::MANAGER_SERVICE_KEY);
        $container->setDefinition(CacheConfigurationPass::DATA_CACHE_POOL, $dataCachePoolDef);

        $compiler = new CacheConfigurationPass();
        $compiler->process($container);

        $this->assertEquals(
            ChainAdapter::class,
            $container->getDefinition(CacheConfigurationPass::DATA_CACHE_POOL)->getClass()
        );
    }

    private function getFilesystemCache(): Definition
    {
        $cacheDefinition = new Definition(
            FilesystemAdapter::class,
            ['', 0, '%kernel.cache_dir%/oro_data']
        );
        $cacheDefinition->setAbstract(true);

        return $cacheDefinition;
    }
}
