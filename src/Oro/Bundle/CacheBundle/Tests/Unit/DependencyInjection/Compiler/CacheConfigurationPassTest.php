<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class CacheConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    public function testCacheDefinitions()
    {
        $container = new ContainerBuilder();

        $compiler = new CacheConfigurationPass();
        $compiler->process($container);

        $fileCacheDef = new Definition(
            'Oro\Bundle\CacheBundle\Provider\FilesystemCache',
            ['%kernel.cache_dir%/oro']
        );
        $fileCacheDef->setAbstract(true);
        $this->assertEquals(
            $fileCacheDef,
            $container->getDefinition(CacheConfigurationPass::FILE_CACHE_SERVICE)
        );

        $dataCacheDef = new Definition(
            'Oro\Bundle\CacheBundle\Provider\FilesystemCache',
            ['%kernel.cache_dir%/oro_data']
        );
        $dataCacheDef->setAbstract(true);
        $this->assertEquals(
            $dataCacheDef,
            $container->getDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE)
        );
    }

    public function testExistCacheDefinitionsShouldNotBeChanged()
    {
        $fileCacheDef = new Definition('TestFileCache');
        $dataCacheDef = new Definition('TestDataCache');

        $container = new ContainerBuilder();
        $container->setDefinition(CacheConfigurationPass::FILE_CACHE_SERVICE, $fileCacheDef);
        $container->setDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE, $dataCacheDef);

        $compiler = new CacheConfigurationPass();
        $compiler->process($container);

        $this->assertEquals(
            $fileCacheDef,
            $container->getDefinition(CacheConfigurationPass::FILE_CACHE_SERVICE)
        );
        $this->assertEquals(
            $dataCacheDef,
            $container->getDefinition(CacheConfigurationPass::DATA_CACHE_SERVICE)
        );
    }

    public function testDataCacheManagerConfiguration()
    {
        $dataCacheManagerDef  = new Definition('Oro\Bundle\CacheBundle\Manager\OroDataCacheManager');
        $fileCacheDef         = new DefinitionDecorator(CacheConfigurationPass::FILE_CACHE_SERVICE);
        $abstractFileCacheDef = new DefinitionDecorator(CacheConfigurationPass::FILE_CACHE_SERVICE);
        $abstractFileCacheDef->setAbstract(true);
        $dataCacheDef         = new DefinitionDecorator(CacheConfigurationPass::DATA_CACHE_SERVICE);
        $abstractDataCacheDef = new DefinitionDecorator(CacheConfigurationPass::FILE_CACHE_SERVICE);
        $abstractDataCacheDef->setAbstract(true);
        $otherCacheDef        = new DefinitionDecorator('some_abstract_cache');

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
}
