<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\EventListener;

use Oro\Bundle\UIBundle\EventListener\SymfonyDebugToolbarReplaceListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class SymfonyDebugToolbarReplaceListenerTest extends TestCase
{
    /** @var KernelInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $kernel;

    /** @var SymfonyDebugToolbarReplaceListener */
    private $listener;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);

        $this->listener = new SymfonyDebugToolbarReplaceListener($this->kernel);
    }

    public function testOnKernelResponseNoDebug(): void
    {
        $response = new Response();

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->kernel->expects(self::any())
            ->method('isDebug')
            ->willReturn(false);

        $this->listener->onKernelResponse($event);

        self::assertNull($response->headers->get('Symfony-Debug-Toolbar-Replace'));
    }

    public function testOnKernelResponseNotAjaxRequest(): void
    {
        $response = new Response();

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->kernel->expects(self::any())
            ->method('isDebug')
            ->willReturn(true);

        $this->listener->onKernelResponse($event);

        self::assertNull($response->headers->get('Symfony-Debug-Toolbar-Replace'));
    }

    public function testOnKernelResponseNotHashNavigation(): void
    {
        $this->kernel->expects(self::any())
            ->method('isDebug')
            ->willReturn(true);

        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = new Response();

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        self::assertNull($response->headers->get('Symfony-Debug-Toolbar-Replace'));
    }

    public function testOnKernelResponseWithHashNavigationHeader(): void
    {
        $this->kernel->expects(self::any())
            ->method('isDebug')
            ->willReturn(true);

        $request = new Request();
        $request->headers->set('x-oro-hash-navigation', 1);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $response = new Response();

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        $this->assertEquals(1, $response->headers->get('Symfony-Debug-Toolbar-Replace'));
    }

    public function testOnKernelResponse(): void
    {
        $this->kernel->expects(self::any())
            ->method('isDebug')
            ->willReturn(true);

        $request = new Request();
        $request->request->set('x-oro-hash-navigation', 1);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $response = new Response();

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        $this->assertEquals(1, $response->headers->get('Symfony-Debug-Toolbar-Replace'));
    }
}
