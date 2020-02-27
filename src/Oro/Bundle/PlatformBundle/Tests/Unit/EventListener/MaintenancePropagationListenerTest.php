<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener;

use Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;
use Oro\Bundle\PlatformBundle\EventListener\MaintenancePropagationListener;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class MaintenancePropagationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MaintenancePropagationListener */
    private $listener;

    protected function setUp()
    {
        $this->listener = new MaintenancePropagationListener();
    }

    public function testOnKernelRequestWhenServiceUnavailableException(): void
    {
        $requestEvent = $this->createMock(GetResponseEvent::class);
        $requestEvent
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request = new Request());

        $request->attributes->set('exception', new ServiceUnavailableException());

        $requestEvent
            ->expects($this->once())
            ->method('stopPropagation');

        $this->listener->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestWhenFlattenException(): void
    {
        $requestEvent = $this->createMock(GetResponseEvent::class);
        $requestEvent
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request = new Request());

        $request->attributes->set('exception', $exception = $this->createMock(FlattenException::class));

        $exception
            ->expects($this->once())
            ->method('getClass')
            ->willReturn(ServiceUnavailableException::class);

        $requestEvent
            ->expects($this->once())
            ->method('stopPropagation');

        $this->listener->onKernelRequest($requestEvent);
    }

    /**
     * @dataProvider requestDataProvider
     *
     * @param Request $request
     */
    public function testOnKernelRequest(Request $request): void
    {
        $requestEvent = $this->createMock(GetResponseEvent::class);
        $requestEvent
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $requestEvent
            ->expects($this->never())
            ->method('stopPropagation');

        $this->listener->onKernelRequest($requestEvent);
    }

    /**
     * @return array
     */
    public function requestDataProvider(): array
    {
        $requestWithFlattenException = new Request();
        $requestWithFlattenException->attributes->set('exception', $this->createMock(FlattenException::class));

        $requestWithException = new Request();
        $requestWithException->attributes->set('exception', $this->createMock(\Exception::class));

        return [
            [
                'request' => new Request(),
            ],
            [
                'request' => $requestWithFlattenException,
            ],
            [
                'request' => $requestWithException,
            ],
        ];
    }

    public function testOnKernelControllerWhenServiceUnavailableException(): void
    {
        $filterControllerEvent = $this->createMock(FilterControllerEvent::class);
        $filterControllerEvent
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request = new Request());

        $request->attributes->set('exception', new ServiceUnavailableException());

        $filterControllerEvent
            ->expects($this->once())
            ->method('stopPropagation');

        $this->listener->onKernelController($filterControllerEvent);
    }

    public function testOnKernelControllerWhenFlattenException(): void
    {
        $filterControllerEvent = $this->createMock(FilterControllerEvent::class);
        $filterControllerEvent
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request = new Request());

        $request->attributes->set('exception', $exception = $this->createMock(FlattenException::class));

        $exception
            ->expects($this->once())
            ->method('getClass')
            ->willReturn(ServiceUnavailableException::class);

        $filterControllerEvent
            ->expects($this->once())
            ->method('stopPropagation');

        $this->listener->onKernelController($filterControllerEvent);
    }

    /**
     * @dataProvider requestDataProvider
     *
     * @param Request $request
     */
    public function testOnKernelController(Request $request): void
    {
        $filterControllerEvent = $this->createMock(FilterControllerEvent::class);
        $filterControllerEvent
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $filterControllerEvent
            ->expects($this->never())
            ->method('stopPropagation');

        $this->listener->onKernelController($filterControllerEvent);
    }
}
