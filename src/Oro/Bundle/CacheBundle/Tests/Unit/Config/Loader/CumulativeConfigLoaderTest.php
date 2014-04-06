<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Config\Loader;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceInfo;
use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\CacheBundle\Config\CumulativeResource;
use Oro\Bundle\CacheBundle\Config\Loader\CumulativeConfigLoader;
use Oro\Bundle\CacheBundle\Config\Loader\YamlCumulativeFileLoader;
use Oro\Bundle\CacheBundle\Tests\Unit\Config\Loader\Fixtures\Bundle\TestBundle\TestBundle;

class CumulativeConfigLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegisterResources()
    {
        $resourceRelativePath = 'Resources/config/test.yml';
        $resource             = new CumulativeResource($resourceRelativePath);
        $resourceLoader       = new YamlCumulativeFileLoader($resourceRelativePath);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->registerResource('test_group', $resourceLoader);

        $container = new ContainerBuilder();
        $loader    = new CumulativeConfigLoader($container);
        $loader->registerResources('test_group');

        $this->assertCount(1, $container->getResources());
        $this->assertEquals($resource, $container->getResources()[0]);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The container builder must not be null.
     */
    public function testRegisterResourcesWithoutContainer()
    {
        $loader = new CumulativeConfigLoader();
        $loader->registerResources('test_group');
    }

    public function testLoad()
    {
        $resourceRelativePath = 'Resources/config/test.yml';
        $resource             = new CumulativeResource($resourceRelativePath);
        $bundle               = new TestBundle();
        $resourceLoader       = new YamlCumulativeFileLoader($resourceRelativePath);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle->getName() => get_class($bundle)])
            ->registerResource('test_group', $resourceLoader);

        $container = new ContainerBuilder();
        $loader    = new CumulativeConfigLoader($container);
        $result    = $loader->load('test_group');

        $this->assertEquals(
            [
                new CumulativeResourceInfo(
                    get_class($bundle),
                    'test',
                    str_replace('/', DIRECTORY_SEPARATOR, $bundle->getPath() . '/' . $resourceRelativePath),
                    ['test' => 123]
                )
            ],
            $result
        );

        $this->assertCount(1, $container->getResources());
        $this->assertEquals($resource, $container->getResources()[0]);
    }

    public function testLoadWithoutContainer()
    {
        $resourceRelativePath = 'Resources/config/test.yml';
        $bundle               = new TestBundle();
        $resourceLoader       = new YamlCumulativeFileLoader($resourceRelativePath);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle->getName() => get_class($bundle)])
            ->registerResource('test_group', $resourceLoader);

        $loader = new CumulativeConfigLoader();
        $result = $loader->load('test_group');

        $this->assertEquals(
            [
                new CumulativeResourceInfo(
                    get_class($bundle),
                    'test',
                    str_replace('/', DIRECTORY_SEPARATOR, $bundle->getPath() . '/' . $resourceRelativePath),
                    ['test' => 123]
                )
            ],
            $result
        );
    }
}
