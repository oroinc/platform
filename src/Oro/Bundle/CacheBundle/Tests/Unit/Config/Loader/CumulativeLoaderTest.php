<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Config\Loader;

use Oro\Bundle\CacheBundle\Config\CumulativeResource;
use Oro\Bundle\CacheBundle\Config\Loader\CumulativeLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CumulativeLoaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $holder;

    /** @var CumulativeLoader */
    private $loader;

    protected function setUp()
    {
        $this->holder = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeLoaderHolder');
        $this->loader = new CumulativeLoader($this->holder);
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
        $resourceLoader = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');
        $resourceLoader->expects($this->once())
            ->method('getResource')
            ->will($this->returnValue('test'));
        $this->loader->addResourceLoader($resourceLoader);

        $container = new ContainerBuilder();
        $this->loader->registerResources($container);

        $this->assertCount(1, $container->getResources());
        $this->assertEquals(new CumulativeResource('test'), $container->getResources()[0]);
    }

    public function testLoad()
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $this->holder->expects($this->once())
            ->method('getBundles')
            ->will($this->returnValue([$bundle]));

        $resourceLoader = $this->getMock('Oro\Bundle\CacheBundle\Config\Loader\CumulativeResourceLoader');
        $resourceLoader->expects($this->once())
            ->method('load')
            ->with($this->identicalTo($bundle))
            ->will($this->returnValue(['test' => 123]));
        $resourceLoader->expects($this->once())
            ->method('getResource')
            ->will($this->returnValue('test'));
        $this->loader->addResourceLoader($resourceLoader);

        $container = new ContainerBuilder();
        $result = $this->loader->load($container);

        $this->assertEquals(
            [
                ['test' => 123]
            ],
            $result
        );

        $this->assertCount(1, $container->getResources());
        $this->assertEquals(new CumulativeResource('test'), $container->getResources()[0]);
    }
}
