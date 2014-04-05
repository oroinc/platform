<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Config\Loader;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceInfo;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\CacheBundle\Config\CumulativeResource;
use Oro\Bundle\CacheBundle\Config\Loader\CumulativeLoader;
use Oro\Bundle\CacheBundle\Config\Loader\YamlCumulativeFileLoader;
use Oro\Bundle\CacheBundle\Tests\Unit\Config\Loader\Fixtures\Bundle\TestBundle\TestBundle;

class CumulativeLoaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var CumulativeLoader */
    private $loader;

    protected function setUp()
    {
        $this->loader = new CumulativeLoader();
    }

    public function testResourceLoaders()
    {
        $this->assertCount(0, $this->loader->getResourceLoaders());

        $resourceLoader = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');
        $this->loader->addResourceLoader($resourceLoader);
        $this->assertCount(1, $this->loader->getResourceLoaders());
        $this->assertSame($resourceLoader, $this->loader->getResourceLoaders()[0]);
    }

    public function testRegisterResources()
    {
        $resource = new CumulativeResource('test');

        $resourceLoader = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');
        $resourceLoader->expects($this->once())
            ->method('registerResource')
            ->will(
                $this->returnCallback(
                    function (ContainerBuilder $container) use ($resource) {
                        $container->addResource($resource);
                    }
                )
            );
        $this->loader->addResourceLoader($resourceLoader);

        $container = new ContainerBuilder();
        $this->loader->registerResources($container);

        $this->assertCount(1, $container->getResources());
        $this->assertSame($resource, $container->getResources()[0]);
    }

    public function testLoad()
    {
        $resourceRelativePath = 'Resources/config/test.yml';
        $resource = new CumulativeResource($resourceRelativePath);

        $bundle = new TestBundle();
        $this->loader->setBundles([$bundle->getName() => get_class($bundle)]);

        $resourceLoader = new YamlCumulativeFileLoader($resourceRelativePath);
        $this->loader->addResourceLoader($resourceLoader);

        $container = new ContainerBuilder();
        $result = $this->loader->load($container);

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
}
