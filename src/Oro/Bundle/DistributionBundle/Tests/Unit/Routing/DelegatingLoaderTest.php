<?php

declare(strict_types=1);

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Routing;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Oro\Bundle\DistributionBundle\Routing\DelegatingLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DelegatingLoaderTest extends TestCase
{
    private LoaderInterface&MockObject $decoratedLoader;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private DelegatingLoader $loader;

    #[\Override]
    protected function setUp(): void
    {
        $this->decoratedLoader = $this->createMock(LoaderInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->loader = new DelegatingLoader($this->decoratedLoader, $this->eventDispatcher);
    }

    public function testLoadDispatchesEventAndReturnsModifiedCollection(): void
    {
        $resource = 'some_resource';
        $type = 'some_type';

        $routes = new RouteCollection();

        $this->decoratedLoader->expects(self::once())
            ->method('load')
            ->with($resource, $type)
            ->willReturn($routes);

        $newRoute = $this->createMock(Route::class);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(RouteCollectionEvent::class), RouteCollectionEvent::ALL)
            ->willReturnCallback(function (RouteCollectionEvent $event) use ($newRoute) {
                $event->getCollection()->add('sample_route', $newRoute);

                return $event;
            });

        $result = $this->loader->load($resource, $type);

        self::assertSame($routes, $result);
        self::assertContains($newRoute, $result);
    }

    public function testSupportsDelegatesToDecoratedLoader(): void
    {
        $resource = 'some_resource';
        $type = 'some_type';

        $this->decoratedLoader->expects(self::once())
            ->method('supports')
            ->with($resource, $type)
            ->willReturn(true);

        self::assertTrue($this->loader->supports($resource, $type));
    }
}
