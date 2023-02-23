<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\EventListener;

use Oro\Bundle\ApiBundle\EventListener\UnhandledApiErrorExceptionListener;
use Oro\Bundle\ApiBundle\Request\ApiRequestHelper;
use Oro\Bundle\ApiBundle\Request\Rest\RequestActionHandler;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class UnhandledApiErrorExceptionListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestActionHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $handler;

    /** @var ApiRequestHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $apiRequestHelper;

    /** @var UnhandledApiErrorExceptionListener */
    private $listener;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(RequestActionHandler::class);
        $this->apiRequestHelper = $this->createMock(ApiRequestHelper::class);

        $container = TestContainerBuilder::create()
            ->add(RequestActionHandler::class, $this->handler)
            ->getContainer($this);

        $this->listener = new UnhandledApiErrorExceptionListener(
            $container,
            $this->apiRequestHelper
        );
    }

    private function getEvent(Request $request, \Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }

    public function testGetSubscribedServices(): void
    {
        self::assertEquals(
            [
                RequestActionHandler::class
            ],
            UnhandledApiErrorExceptionListener::getSubscribedServices()
        );
    }

    public function testForNotApiRequest(): void
    {
        $request = Request::create('http://test.com/product/view/1');

        $this->apiRequestHelper->expects(self::once())
            ->method('isApiRequest')
            ->with($request->getPathInfo())
            ->willReturn(false);
        $this->handler->expects(self::never())
            ->method('handleUnhandledError');

        $event = $this->getEvent($request, new \Exception('some error'));
        $this->listener->onKernelException($event);
        self::assertNull($event->getResponse());
    }

    public function testForApiRequest(): void
    {
        $request = Request::create('http://test.com/api/products/1');
        $exception = new \Exception('some error');
        $response = $this->createMock(Response::class);

        $this->apiRequestHelper->expects(self::once())
            ->method('isApiRequest')
            ->with($request->getPathInfo())
            ->willReturn(true);
        $this->handler->expects(self::once())
            ->method('handleUnhandledError')
            ->with(self::identicalTo($request), self::identicalTo($exception))
            ->willReturn($response);

        $event = $this->getEvent($request, $exception);
        $this->listener->onKernelException($event);
        self::assertSame($response, $event->getResponse());
    }
}
