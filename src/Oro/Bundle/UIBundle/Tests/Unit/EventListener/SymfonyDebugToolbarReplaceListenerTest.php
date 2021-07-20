<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\EventListener;

use Oro\Bundle\UIBundle\EventListener\SymfonyDebugToolbarReplaceListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class SymfonyDebugToolbarReplaceListenerTest extends TestCase
{
    /**
     * @var SymfonyDebugToolbarReplaceListener
     */
    private $listener;

    /**
     * @var MockObject|KernelInterface
     */
    private $kernel;

    /**
     * @var MockObject|ResponseEvent
     */
    protected $event;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->listener = new SymfonyDebugToolbarReplaceListener($this->kernel);

        $this->event = $this->createMock(ResponseEvent::class);
    }

    public function testOnKernelResponseNoDebug(): void
    {
        $this->kernel->method('isDebug')
            ->willReturn(false);

        $this->event->expects($this->never())
            ->method('getResponse');

        $this->listener->onKernelResponse($this->event);
    }

    public function testOnKernelResponseNotAjaxRequest(): void
    {
        $this->kernel->method('isDebug')
            ->willReturn(true);

        $request = new Request();
        $request->headers->set('x-oro-hash-navigation', 1);
        $this->event->method('getRequest')
            ->willReturn($request);
        $this->event->expects($this->never())
            ->method('getResponse');

        $this->listener->onKernelResponse($this->event);
    }

    public function testOnKernelResponseNotHashNavigation(): void
    {
        $this->kernel->method('isDebug')
            ->willReturn(true);

        $request = new Request();
        $this->event->method('getRequest')
            ->willReturn($request);
        $this->event->expects($this->never())
            ->method('getResponse');

        $this->listener->onKernelResponse($this->event);
    }

    public function testOnKernelResponseWithHashNavigationHeader(): void
    {
        $this->kernel->method('isDebug')
            ->willReturn(true);

        $request = new Request();
        $request->headers->set('x-oro-hash-navigation', 1);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $response = new Response();
        $this->event->method('getRequest')
            ->willReturn($request);
        $this->event->method('getResponse')
            ->willReturn($response);

        $this->listener->onKernelResponse($this->event);

        $this->assertEquals(1, $this->event->getResponse()->headers->get('Symfony-Debug-Toolbar-Replace'));
    }

    public function testOnKernelResponse(): void
    {
        $this->kernel->method('isDebug')
            ->willReturn(true);

        $request = new Request();
        $request->request->set('x-oro-hash-navigation', 1);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $response = new Response();
        $this->event->method('getRequest')
            ->willReturn($request);
        $this->event->method('getResponse')
            ->willReturn($response);

        $this->listener->onKernelResponse($this->event);

        $this->assertEquals(1, $this->event->getResponse()->headers->get('Symfony-Debug-Toolbar-Replace'));
    }
}
