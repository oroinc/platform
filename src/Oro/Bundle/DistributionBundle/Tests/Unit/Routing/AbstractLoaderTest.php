<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Routing;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Oro\Bundle\DistributionBundle\Routing\AbstractLoader;

abstract class AbstractLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|KernelInterface
     */
    protected $kernel;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RouteOptionsResolverInterface
     */
    protected $routeOptionsResolver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var LoaderResolver
     */
    protected $loaderResolver;

    protected function setUp()
    {
        $this->kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $this->routeOptionsResolver = $this->getMock('Oro\Component\Routing\Resolver\RouteOptionsResolverInterface');
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->loaderResolver = new LoaderResolver([new YamlFileLoader(new FileLocator())]);
    }

    protected function tearDown()
    {
        unset($this->kernel, $this->routeOptionsResolver, $this->eventDispatcher);
    }

    public function testSupportsFailed()
    {
        $this->assertFalse($this->getLoader()->supports(null, 'not_supported'));
    }

    /**
     * @param array $expected
     *
     * @dataProvider loadDataProvider
     */
    public function testLoad(array $expected)
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures';
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle->expects($this->any())->method('getPath')->willReturn($dir);

        $this->kernel->expects($this->once())->method('getBundles')->willReturn([$bundle, $bundle]);

        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(
            $this->isType('string'),
            $this->callback(
                function (RouteCollectionEvent $event) use ($expected) {
                    $this->assertEquals($expected, $event->getCollection()->all());

                    return true;
                }
            )
        );

        $this->assertEquals($expected, $this->getLoader()->load('file', 'type')->all());
    }

    public function testDispatchEventWithoutEventDispatcher()
    {
        $this->kernel->expects($this->once())->method('getBundles')->willReturn([]);
        $this->eventDispatcher->expects($this->never())->method('dispatch');
        $this->assertEquals(
            [],
            $this->getLoaderWithoutEventDispatcher()->load('file', 'type')->all()
        );
    }

    public function testLoadWithEmptyCache()
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures';
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle->expects($this->any())->method('getPath')->willReturn($dir);
        $this->kernel->expects($this->once())->method('getBundles')->willReturn([$bundle]);

        $cache = $this->getMockBuilder('Oro\Bundle\DistributionBundle\Routing\SharedData')
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects($this->once())
            ->method('getRoutes')
            ->with($this->isType('string'))
            ->willReturn(null);
        $cache->expects($this->once())
            ->method('setRoutes')
            ->with($this->isType('string'), $this->isInstanceOf('Symfony\Component\Routing\RouteCollection'));

        $loader = $this->getLoader();
        $loader->setCache($cache);
        $loader->load('file', 'type')->all();
    }

    public function testLoadWithCachedData()
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures';
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle->expects($this->any())->method('getPath')->willReturn($dir);
        $this->kernel->expects($this->once())->method('getBundles')->willReturn([$bundle]);

        $cache = $this->getMockBuilder('Oro\Bundle\DistributionBundle\Routing\SharedData')
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects($this->once())
            ->method('getRoutes')
            ->with($this->isType('string'))
            ->willReturn(new RouteCollection());
        $cache->expects($this->never())
            ->method('setRoutes');

        $loader = $this->getLoader();
        $loader->setCache($cache);
        $loader->load('file', 'type')->all();
    }

    /**
     * @return AbstractLoader
     */
    abstract public function getLoader();

    /**
     * @return AbstractLoader
     */
    abstract public function getLoaderWithoutEventDispatcher();

    /**
     * @return array
     */
    abstract public function loadDataProvider();
}
