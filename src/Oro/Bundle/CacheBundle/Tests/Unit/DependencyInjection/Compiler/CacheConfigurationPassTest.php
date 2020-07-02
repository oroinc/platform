<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\VoidCache;
use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass;
use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheChain;
use Oro\Component\Config\Cache\ConfigCacheWarmer;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CacheConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    public function testCacheDefinitions()
    {
        $container = new ContainerBuilder();
        $container->register(CacheConfigurationPass::MANAGER_SERVICE_KEY);

        $compiler = new CacheConfigurationPass();
        $compiler->process($container);

        $dataCacheDef = new Definition(
            MemoryCacheChain::class,
            [$this->getFilesystemCache('%kernel.cache_dir%/oro_data')]
        );
        $dataCacheDef->setAbstract(true);
        $this->assertEquals(
            $dataCacheDef,
            $container->getDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE)
        );
    }

    public function testExistingCacheDefinitionsShouldNotBeChanged()
    {
        $dataCacheDef = new Definition(ArrayCache::class);

        $container = new ContainerBuilder();
        $container->register(CacheConfigurationPass::MANAGER_SERVICE_KEY);
        $container->setDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE, $dataCacheDef);

        $compiler = new CacheConfigurationPass();
        $compiler->process($container);

        $this->assertEquals(
            (new Definition(MemoryCacheChain::class, [$dataCacheDef]))->setAbstract(true),
            $container->getDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE)
        );
    }

    public function testExceptionIsThrownWhenInvalidCacheProviderGiven()
    {
        $dataCacheDef = new Definition(Cache::class);

        $container = new ContainerBuilder();
        $container->register(CacheConfigurationPass::MANAGER_SERVICE_KEY);
        $container->setDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE, $dataCacheDef);

        $compiler = new CacheConfigurationPass();

        $this->expectException(\InvalidArgumentException::class);
        $compiler->process($container);
    }

    public function testAbstractDefinitionWithoutClass()
    {
        $abstractDataCacheDef = new Definition();
        $abstractDataCacheDef->setAbstract(true);

        $container = new ContainerBuilder();
        $container->register(CacheConfigurationPass::MANAGER_SERVICE_KEY);
        $container->setDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE, $abstractDataCacheDef);

        $compiler = new CacheConfigurationPass();

        $compiler->process($container);
    }

    public function testAbstractDefinitionWithSupportedClass()
    {
        $abstractDataCacheDef = new Definition(VoidCache::class);
        $abstractDataCacheDef->setAbstract(true);

        $container = new ContainerBuilder();
        $container->register(CacheConfigurationPass::MANAGER_SERVICE_KEY);
        $container->setDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE, $abstractDataCacheDef);

        $compiler = new CacheConfigurationPass();

        $compiler->process($container);
    }

    public function testAbstractDefinitionWithUnsupportedClass()
    {
        $abstractDataCacheDef = new Definition(Cache::class);
        $abstractDataCacheDef->setAbstract(true);

        $container = new ContainerBuilder();
        $container->register(CacheConfigurationPass::MANAGER_SERVICE_KEY);
        $container->setDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE, $abstractDataCacheDef);

        $compiler = new CacheConfigurationPass();

        $this->expectException(\InvalidArgumentException::class);
        $compiler->process($container);
    }

    public function testDataCacheManagerConfiguration()
    {
        $dataCacheManagerDef = new Definition(OroDataCacheManager::class);
        $dataCacheDef = new ChildDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE);
        $abstractDataCacheDef = new ChildDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE);
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

    /**
     * @param string $path
     *
     * @return Definition
     */
    private function getFilesystemCache($path)
    {
        $cacheDefinition = new Definition(
            FilesystemCache::class,
            [$path]
        );
        $cacheDefinition->setAbstract(true);

        return $cacheDefinition;
    }
}
