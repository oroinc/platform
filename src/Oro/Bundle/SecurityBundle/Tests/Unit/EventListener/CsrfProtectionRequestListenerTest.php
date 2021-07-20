<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\SecurityBundle\Csrf\CookieTokenStorage;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\SecurityBundle\EventListener\CsrfProtectionRequestListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CsrfProtectionRequestListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CsrfRequestManager|\PHPUnit\Framework\MockObject\MockObject */
    private $csrfRequestManager;

    /** @var CsrfProtectionRequestListener */
    private $listener;

    protected function setUp(): void
    {
        $this->csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $this->listener = new CsrfProtectionRequestListener($this->csrfRequestManager);
    }

    public function testOnKernelControllerNotMasterRequest()
    {
        /** @var FilterControllerEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FilterControllerEvent::class);
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(false);

        $event->expects($this->never())
            ->method('getRequest');

        $this->csrfRequestManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerCsrfTokenRefreshWhenCookieNotPresent()
    {
        $request = Request::create('/');

        /** @var FilterControllerEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FilterControllerEvent::class);
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->csrfRequestManager->expects($this->once())
            ->method('refreshRequestToken');

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerNoCsrfProtection()
    {
        $request = Request::create('/');
        $request->cookies->set(CsrfRequestManager::CSRF_TOKEN_ID, 'test');

        /** @var FilterControllerEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FilterControllerEvent::class);
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->csrfRequestManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerDisabledCheck()
    {
        $csrfProtection = new CsrfProtection(['enabled' => false]);

        $request = Request::create('/');
        $request->attributes->set('_' . CsrfProtection::ALIAS_NAME, $csrfProtection);
        $request->cookies->set(CsrfRequestManager::CSRF_TOKEN_ID, 'test');

        /** @var FilterControllerEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FilterControllerEvent::class);
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->csrfRequestManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onKernelController($event);
    }

    /**
     * @dataProvider useRequestDataProvider
     * @param bool $useRequest
     */
    public function testOnKernelControllerCsrfPassed($useRequest)
    {
        $csrfProtection = new CsrfProtection(['enabled' => true, 'useRequest' => $useRequest]);

        $request = Request::create('/');
        $request->attributes->set('_' . CsrfProtection::ALIAS_NAME, $csrfProtection);

        /** @var FilterControllerEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FilterControllerEvent::class);
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->csrfRequestManager->expects($this->once())
            ->method('refreshRequestToken')
            ->with();

        $this->csrfRequestManager->expects($this->once())
            ->method('isRequestTokenValid')
            ->with($request, $useRequest)
            ->willReturn(true);

        $this->listener->onKernelController($event);
    }

    /**
     * @dataProvider useRequestDataProvider
     * @param bool $useRequest
     */
    public function testOnKernelControllerCsrfFail($useRequest)
    {
        $csrfProtection = new CsrfProtection(['enabled' => true, 'useRequest' => $useRequest]);

        $request = Request::create('/');
        $request->attributes->set('_' . CsrfProtection::ALIAS_NAME, $csrfProtection);

        /** @var FilterControllerEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FilterControllerEvent::class);
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->csrfRequestManager->expects($this->once())
            ->method('isRequestTokenValid')
            ->with($request, $useRequest)
            ->willReturn(false);

        $this->csrfRequestManager->expects($this->never())
            ->method('refreshRequestToken');

        $this->expectException(AccessDeniedHttpException::class);

        $this->listener->onKernelController($event);
    }

    public function useRequestDataProvider(): array
    {
        return [
            'use request' => [true],
            'do not use request' => [false]
        ];
    }

    public function testOnKernelResponseNotMasterRequest()
    {
        /** @var FilterResponseEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FilterResponseEvent::class);

        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(false);

        $event->expects($this->never())
            ->method('getResponse');

        $this->listener->onKernelResponse($event);
    }

    public function testOnKernelResponseNoCsrfProtection()
    {
        $request = Request::create('/');

        /** @var FilterResponseEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FilterResponseEvent::class);
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $event->expects($this->never())
            ->method('getResponse');

        $this->listener->onKernelResponse($event);
    }

    public function testOnKernelResponseRefreshCookie()
    {
        $cookie = new Cookie('test');

        $request = Request::create('/');
        $request->attributes->set(CookieTokenStorage::CSRF_COOKIE_ATTRIBUTE, $cookie);
        $response = new Response();

        /** @var FilterResponseEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FilterResponseEvent::class);
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $event->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);

        $this->listener->onKernelResponse($event);

        $this->assertEquals([$cookie], $response->headers->getCookies());
    }
}
