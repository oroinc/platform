<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\EventListener\PermissionsPolicyHeaderRequestListener;
use Oro\Bundle\SecurityBundle\Provider\PermissionsPolicyHeaderProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class PermissionsPolicyHeaderRequestListenerTest extends TestCase
{
    private PermissionsPolicyHeaderProvider&MockObject $headerProvider;
    private PermissionsPolicyHeaderRequestListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->headerProvider = $this->createMock(PermissionsPolicyHeaderProvider::class);

        $this->listener = new PermissionsPolicyHeaderRequestListener(
            $this->headerProvider
        );
    }

    public function testNotMasterRequest(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);

        $this->headerProvider->expects($this->never())
            ->method($this->anything());

        $this->listener->onKernelResponse($event);
    }

    public function testDisabledHeader(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        $response = new Response();
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->headerProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->headerProvider->expects($this->never())
            ->method('getDirectivesValue');

        $this->listener->onKernelResponse($event);

        $this->assertFalse($response->headers->has('Permissions-Policy'));
    }

    public function testEnabledHeader(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        $response = new Response();
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->headerProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->headerProvider->expects($this->once())
            ->method('getDirectivesValue')
            ->willReturn('payment=*');

        $this->listener->onKernelResponse($event);

        $this->assertTrue($response->headers->has('Permissions-Policy'));
        $this->assertEquals('payment=*', $response->headers->get('Permissions-Policy'));
    }
}
