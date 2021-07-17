<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener;

use Lexik\Bundle\MaintenanceBundle\Drivers\DatabaseDriver;
use Lexik\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;
use Oro\Bundle\PlatformBundle\EventListener\MaintenanceListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MaintenanceListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider routeProviderWithDebugContext
     */
    public function testRouteFilter(bool $debug, ?string $route, bool $expected): void
    {
        $request = Request::create('');
        $request->attributes->set('_route', $route);

        $event = new GetResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $listener = new MaintenanceListener(
            new DriverFactory(
                $this->getDatabaseDriver(true),
                $this->createMock(TranslatorInterface::class),
                ['class' => DriverFactory::DATABASE_DRIVER, 'options' => null]
            ),
            null,
            null,
            null,
            array(),
            array(),
            null,
            array(),
            null,
            null,
            null,
            $debug
        );

        if ($expected) {
            $this->expectException(ServiceUnavailableException::class);
        }

        $listener->onKernelRequest($event);
    }

    public function routeProviderWithDebugContext(): array
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
     * @param boolean $lock
     * @return DatabaseDriver
     */
    private function getDatabaseDriver(bool $lock = false): DatabaseDriver
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->any())
            ->method('isExists')
            ->willReturn($lock);
        $db->expects($this->any())
            ->method('decide')
            ->willReturn($lock);

        return $db;
    }
}
