<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass;
use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheChain;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CacheConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    public function testCacheDefinitions()
    {
        $container = new ContainerBuilder();

        $compiler = new CacheConfigurationPass();
        $compiler->process($container);

        $fileCacheDef = new Definition(
            MemoryCacheChain::class,
            [$this->getFilesystemCache('%kernel.cache_dir%/oro')]
        );
        $fileCacheDef->setAbstract(true);
        $this->assertEquals(
            $fileCacheDef,
            $container->getDefinition(CacheConfigurationPass::FILE_CACHE_SERVICE)
        );

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
        $fileCacheDef = new Definition(ArrayCache::class);
        $dataCacheDef = new Definition(ArrayCache::class);

        $container = new ContainerBuilder();
        $container->setDefinition(CacheConfigurationPass::FILE_CACHE_SERVICE, $fileCacheDef);
        $container->setDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE, $dataCacheDef);

        $compiler = new CacheConfigurationPass();
        $compiler->process($container);

        $this->assertEquals(
            (new Definition(MemoryCacheChain::class, [$fileCacheDef]))->setAbstract(true),
            $container->getDefinition(CacheConfigurationPass::FILE_CACHE_SERVICE)
        );
        $this->assertEquals(
            (new Definition(MemoryCacheChain::class, [$dataCacheDef]))->setAbstract(true),
            $container->getDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE)
        );
    }

    public function testExceptionIsThrownWhenInvalidCacheProviderGiven()
    {
        $fileCacheDef = new Definition(Cache::class);
        $dataCacheDef = new Definition(Cache::class);

        $container = new ContainerBuilder();
        $container->setDefinition(CacheConfigurationPass::FILE_CACHE_SERVICE, $fileCacheDef);
        $container->setDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE, $dataCacheDef);

        $compiler = new CacheConfigurationPass();

        $this->expectException(\InvalidArgumentException::class);
        $compiler->process($container);
    }

    public function testDataCacheManagerConfiguration()
    {
        $dataCacheManagerDef  = new Definition('Oro\Bundle\CacheBundle\Manager\OroDataCacheManager');
        $fileCacheDef         = new ChildDefinition(CacheConfigurationPass::FILE_CACHE_SERVICE);
        $abstractFileCacheDef = new ChildDefinition(CacheConfigurationPass::FILE_CACHE_SERVICE);
        $abstractFileCacheDef->setAbstract(true);
        $dataCacheDef         = new ChildDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE);
        $abstractDataCacheDef = new ChildDefinition(CacheConfigurationPass::FILE_CACHE_SERVICE);
        $abstractDataCacheDef->setAbstract(true);
        $otherCacheDef        = new ChildDefinition('some_abstract_cache');

        $container = new ContainerBuilder();
        $container->setDefinition(CacheConfigurationPass::MANAGER_SERVICE_KEY, $dataCacheManagerDef);
        $container->setDefinition('file_cache', $fileCacheDef);
        $container->setDefinition('abstract_file_cache', $abstractFileCacheDef);
        $container->setDefinition('data_cache', $dataCacheDef);
        $container->setDefinition('abstract_data_cache', $abstractDataCacheDef);
        $container->setDefinition('other_cache', $otherCacheDef);

        $compiler = new CacheConfigurationPass();
        $compiler->process($container);

        $expectedDataCacheManagerDef = new Definition('Oro\Bundle\CacheBundle\Manager\OroDataCacheManager');
        $expectedDataCacheManagerDef->addMethodCall('registerCacheProvider', [new Reference('file_cache')]);
        $expectedDataCacheManagerDef->addMethodCall('registerCacheProvider', [new Reference('data_cache')]);
        $this->assertEquals(
            $expectedDataCacheManagerDef,
            $container->getDefinition(CacheConfigurationPass::MANAGER_SERVICE_KEY)
        );
    }

    /**
     * @param string $path
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
