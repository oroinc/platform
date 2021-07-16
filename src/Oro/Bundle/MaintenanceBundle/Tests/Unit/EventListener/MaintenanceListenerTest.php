<?php

namespace Oro\Bundle\MaintenanceBundle\Tests\Unit\EventListener;

use Oro\Bundle\MaintenanceBundle\Drivers\AbstractDriver;
use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Oro\Bundle\MaintenanceBundle\EventListener\MaintenanceListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MaintenanceListenerTest extends \PHPUnit\Framework\TestCase
{
    private AbstractDriver|\PHPUnit\Framework\MockObject\MockObject $driver;

    private DriverFactory|\PHPUnit\Framework\MockObject\MockObject $driverFactory;

    private RouterListener|\PHPUnit\Framework\MockObject\MockObject $routerListener;

    private MaintenanceListener $maintenanceListener;

    protected function setUp(): void
    {
        $this->driver = $this->createMock(AbstractDriver::class);

        $this->driverFactory = $this->createMock(DriverFactory::class);
        $this->driverFactory->expects(self::any())
            ->method('getDriver')
            ->willReturn($this->driver);

        $this->routerListener = $this->createMock(RouterListener::class);

        $this->maintenanceListener = $this->getMaintenanceListener();
    }

    public function testOnKernelRequestWhenMaintenanceAndMasterRequest(): void
    {
        $this->driver->expects(self::once())
            ->method('decide')
            ->willReturn(true);

        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::once())
            ->method('getRequestType')
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $this->routerListener->expects(self::once())
            ->method('onKernelRequest')
            ->with($event);

        $this->maintenanceListener->onKernelRequest($event);
    }

    public function testOnKernelRequestWhenMaintenanceAndSubRequest(): void
    {
        $this->driver->expects(self::never())
            ->method('decide');

        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::once())
            ->method('getRequestType')
            ->willReturn(HttpKernelInterface::SUB_REQUEST);

        $this->routerListener->expects(self::never())
            ->method('onKernelRequest');

        $this->maintenanceListener->onKernelRequest($event);
    }

    public function testOnKernelRequestOnKernelRequestWhenNotMaintenance(): void
    {
        $this->driver->expects(self::once())
            ->method('decide')
            ->willReturn(false);

        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::once())
            ->method('getRequestType')
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $this->routerListener->expects(self::never())
            ->method('onKernelRequest');

        $this->maintenanceListener->onKernelRequest($event);
    }

    /**
     * @dataProvider routeFilterDataProvider
     */
    public function testRouteFilter(bool $debug, ?string $route, bool $expected): void
    {
        $request = Request::create('');
        $request->attributes->set('_route', $route);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->mockDriver();

        $listener = $this->getMaintenanceListener(null, null, [], [], [], $debug);

        if ($expected) {
            $this->expectException(ServiceUnavailableHttpException::class);
        }

        $listener->onKernelRequest($event);

        self::assertEquals($expected, $event->isPropagationStopped());
    }

    public function routeFilterDataProvider(): array
    {
        return [
            'debug, common route / exception' => [
                'debug' => true,
                'route' => 'route_1',
                'expected' => true,
            ],
            'debug, debug route / no exception' => [
                'debug' => true,
                'route' => '_route_started_with_underscore',
                'expected' => false,
            ],
            'debug, no route / exception' => [
                'debug' => true,
                'route' => 'route_1',
                'expected' => true,
            ],
            'not debug, common route / exception' => [
                'debug' => false,
                'route' => 'route_1',
                'expected' => true,
            ],
            'not debug, debug route / exception' => [
                'debug' => false,
                'route' => '_route_started_with_underscore',
                'expected' => true,
            ],
            'not debug, no route / exception' => [
                'debug' => false,
                'route' => 'route_1',
                'expected' => true,
            ]
        ];
    }

    /**
     * @dataProvider pathFilterDataProvider
     */
    public function testPathFilter(?string $path, bool $expected): void
    {
        $request = Request::create('http://test.com/foo?bar=baz');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->mockDriver();

        $listener = $this->getMaintenanceListener($path);

        if ($expected) {
            $this->expectException(ServiceUnavailableHttpException::class);
        }

        $listener->onKernelRequest($event);

        self::assertEquals($expected, $event->isPropagationStopped());
    }

    public function pathFilterDataProvider(): array
    {
        return [
            'without path' => [
                'path' => null,
                'expected' => true,
            ],
            'empty path' => [
                'path' => '',
                'expected' => true,
            ],
            'non matching path' => [
                'path' => '/bar',
                'expected' => true,
            ],
            'matching path' => [
                'path' => '/foo',
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider hostFilterDataProvider
     */
    public function testHostFilter(?string $host, bool $expected): void
    {
        $request = Request::create('http://test.com/foo?bar=baz');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->mockDriver();

        $listener = $this->getMaintenanceListener('/barfoo', $host);

        if ($expected) {
            $this->expectException(ServiceUnavailableHttpException::class);
        }

        $listener->onKernelRequest($event);

        self::assertEquals($expected, $event->isPropagationStopped());
    }

    public function hostFilterDataProvider(): array
    {
        return [
            'without host' => [
                'host' => null,
                'expected' => true,
            ],
            'empty host' => [
                'host' => '',
                'expected' => true,
            ],
            'non matching host' => [
                'host' => 'www.google.com',
                'expected' => true,
            ],
            'matching host' => [
                'host' => 'test.com',
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider ipFilterDataProvider
     */
    public function testIPFilter(array $ips, bool $expected): void
    {
        $request = Request::create('http://test.com/foo?bar=baz');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->mockDriver();

        $listener = $this->getMaintenanceListener('/barfoo', 'www.google.com', $ips);

        if ($expected) {
            $this->expectException(ServiceUnavailableHttpException::class);
        }

        $listener->onKernelRequest($event);

        self::assertEquals($expected, $event->isPropagationStopped());
    }

    public function ipFilterDataProvider(): array
    {
        return [
            'empty ips' => [
                'ips' => [],
                'expected' => true,
            ],
            'non matching ips' => [
                'ips' => ['8.8.4.4'],
                'expected' => true,
            ],
            'matching ips' => [
                'ips' => ['127.0.0.1'],
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider queryFilterDataProvider
     */
    public function testQueryFilter(Request $request, ?array $query, bool $expected): void
    {
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->mockDriver();

        $listener = $this->getMaintenanceListener('/barfoo', 'www.google.com', ['8.8.4.4'], $query);

        if ($expected) {
            $this->expectException(ServiceUnavailableHttpException::class);
        }

        $listener->onKernelRequest($event);

        self::assertEquals($expected, $event->isPropagationStopped());
    }

    public function queryFilterDataProvider(): array
    {
        $request = Request::create('http://test.com/foo?bar=baz');
        $postRequest = Request::create('http://test.com/foo?bar=baz', 'POST');

        return [
            'empty query' => [
                'request' => $request,
                'query' => [],
                'expected' => true,
            ],
            'non matching query' => [
                'request' => $request,
                'query' => ['some' => 'attribute'],
                'expected' => true,
            ],
            'matching query' => [
                'request' => $request,
                'query' => ['bar' => 'baz'],
                'expected' => false,
            ],
            'matching post query' => [
                'request' => $postRequest,
                'query' => ['bar' => 'baz'],
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider cookieFilterDataProvider
     */
    public function testCookieFilter(?array $cookies, bool $expected): void
    {
        $request = Request::create('http://test.com/foo', 'GET', [], ['bar' => 'baz']);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->mockDriver();

        $listener = $this->getMaintenanceListener('/barfoo', 'www.google.com', ['8.8.4.4'], ['bar' => 'baz'], $cookies);

        if ($expected) {
            $this->expectException(ServiceUnavailableHttpException::class);
        }

        $listener->onKernelRequest($event);

        self::assertEquals($expected, $event->isPropagationStopped());
    }

    public function cookieFilterDataProvider(): array
    {
        return [
            'empty cookies' => [
                'cookies' => [],
                'expected' => true,
            ],
            'non matching cookie (array)' => [
                'cookies' => ['some' => 'attribute'],
                'expected' => true,
            ],
            'non matching cookie (list)' => [
                'cookies' => ['attribute'],
                'expected' => true,
            ],
            'matching cookie' => [
                'cookies' => ['bar' => 'baz'],
                'expected' => false,
            ],
        ];
    }

    private function mockDriver(): void
    {
        $this->driver->expects(self::any())
            ->method('isExists')
            ->willReturn(true);
        $this->driver->expects(self::any())
            ->method('decide')
            ->willReturn(true);
    }

    private function getMaintenanceListener(
        ?string $path = null,
        ?string $host = null,
        array $ips = [],
        ?array $query = [],
        ?array $cookie = [],
        bool $debug = false
    ): MaintenanceListener {
        return new MaintenanceListener(
            $this->driverFactory,
            $this->routerListener,
            $path,
            $host,
            $ips,
            $query,
            $cookie,
            null,
            [],
            503,
            null,
            null,
            $debug
        );
    }
}
