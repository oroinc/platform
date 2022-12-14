<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Routing;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Oro\Bundle\DistributionBundle\Routing\AbstractLoader;
use Oro\Bundle\DistributionBundle\Routing\SharedData;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

abstract class AbstractLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var KernelInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $kernel;

    /** @var RouteOptionsResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $routeOptionsResolver;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /** @var LoaderResolver */
    protected $loaderResolver;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->routeOptionsResolver = $this->createMock(RouteOptionsResolverInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->loaderResolver = new LoaderResolver([new YamlFileLoader(new FileLocator())]);
    }

    public function testSupportsFailed(): void
    {
        self::assertFalse($this->getLoader()->supports(null, 'not_supported'));
    }

    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad(array $expected): void
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures';
        $bundle = $this->createMock(BundleInterface::class);
        $bundle->expects($this->any())->method('getPath')->willReturn($dir);

        $this->kernel->expects($this->once())->method('getBundles')->willReturn([$bundle, $bundle]);

        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(
            $this->callback(
                function (RouteCollectionEvent $event) use ($expected) {
                    self::assertEquals($expected, $event->getCollection()->all());

                    return true;
                }
            ),
            $this->isType('string')
        );

        $routes = $this->getLoader()->load('file', 'type')->all();
        self::assertEquals($expected, $routes);
        self::assertSame(array_keys($expected), array_keys($routes));
    }

    public function testDispatchEventWithoutEventDispatcher(): void
    {
        $this->kernel->expects($this->once())->method('getBundles')->willReturn([]);
        $this->eventDispatcher->expects($this->never())->method('dispatch');
        self::assertEquals(
            [],
            $this->getLoaderWithoutEventDispatcher()->load('file', 'type')->all()
        );
    }

    /**
     * @dataProvider loadDataProvider
     */
    public function testLoadWithEmptyCache(array $expected): void
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures';
        $bundle = $this->createMock(BundleInterface::class);
        $bundle->expects($this->any())->method('getPath')->willReturn($dir);
        $this->kernel->expects($this->once())->method('getBundles')->willReturn([$bundle]);

        $cache = $this->getMockBuilder(SharedData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects($this->once())
            ->method('getRoutes')
            ->with($this->isType('string'))
            ->willReturn(null);
        $cache->expects($this->once())
            ->method('setRoutes')
            ->with($this->isType('string'), $this->isInstanceOf(RouteCollection::class));

        $loader = $this->getLoader();
        $loader->setCache($cache);
        $routes = $loader->load('file', 'type')->all();

        self::assertEquals($expected, $routes);
        self::assertSame(array_keys($expected), array_keys($routes));
    }

    public function testLoadWithCachedData(): void
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures';
        $bundle = $this->createMock(BundleInterface::class);
        $bundle->expects($this->any())->method('getPath')->willReturn($dir);
        $this->kernel->expects($this->once())->method('getBundles')->willReturn([$bundle]);

        $cache = $this->getMockBuilder(SharedData::class)
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

    abstract public function getLoader(): AbstractLoader;

    abstract public function getLoaderWithoutEventDispatcher(): AbstractLoader;

    abstract public function loadDataProvider(): array;
}
