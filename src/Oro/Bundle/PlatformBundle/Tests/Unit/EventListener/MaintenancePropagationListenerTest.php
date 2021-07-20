<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener;

use Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;
use Oro\Bundle\PlatformBundle\EventListener\MaintenancePropagationListener;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class MaintenancePropagationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MaintenancePropagationListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new MaintenancePropagationListener();
    }

    public function testOnKernelRequestWhenServiceUnavailableException(): void
    {
        $requestEvent = $this->createMock(RequestEvent::class);
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
        $requestEvent = $this->createMock(RequestEvent::class);
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
     * @dataProvider onKernelRequestDataProvider
     */
    public function testOnKernelRequest(Request $request): void
    {
        $requestEvent = $this->createMock(RequestEvent::class);
        $requestEvent
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $requestEvent
            ->expects($this->never())
            ->method('stopPropagation');

        $this->listener->onKernelRequest($requestEvent);
    }

    public function onKernelRequestDataProvider(): array
    {
        $requestWithFlattenException = new Request();
        $requestWithFlattenException->attributes->set('exception', $this->createMock(FlattenException::class));

        $requestWithThrowable = new Request();
        $requestWithThrowable->attributes->set('exception', $this->createMock(\Throwable::class));

        return [
            [
                'request' => new Request(),
            ],
            [
                'request' => $requestWithFlattenException,
            ],
            [
                'request' => $requestWithThrowable,
            ],
        ];
    }
}
