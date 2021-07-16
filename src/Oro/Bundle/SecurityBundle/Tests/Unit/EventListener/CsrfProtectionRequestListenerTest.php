<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\SecurityBundle\Csrf\CookieTokenStorage;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\SecurityBundle\EventListener\CsrfProtectionRequestListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CsrfProtectionRequestListenerTest extends \PHPUnit\Framework\TestCase
{
    private CsrfRequestManager|\PHPUnit\Framework\MockObject\MockObject $csrfRequestManager;

    private CsrfProtectionRequestListener $listener;

    protected function setUp(): void
    {
        $this->csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $this->listener = new CsrfProtectionRequestListener($this->csrfRequestManager);
    }

    public function testOnKernelControllerNotMasterRequest(): void
    {
        $event = $this->createMock(ControllerEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(false);

        $event->expects(self::never())
            ->method('getRequest');

        $this->csrfRequestManager->expects(self::never())
            ->method(self::anything());

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerCsrfTokenRefreshWhenCookieNotPresent(): void
    {
        $request = Request::create('/');

        $event = $this->createMock(ControllerEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $this->csrfRequestManager->expects(self::once())
            ->method('refreshRequestToken');

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerNoCsrfProtection(): void
    {
        $request = Request::create('/');
        $request->cookies->set(CsrfRequestManager::CSRF_TOKEN_ID, 'test');

        $event = $this->createMock(ControllerEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $this->csrfRequestManager->expects(self::never())
            ->method(self::anything());

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerDisabledCheck(): void
    {
        $csrfProtection = new CsrfProtection(['enabled' => false]);

        $request = Request::create('/');
        $request->attributes->set('_' . CsrfProtection::ALIAS_NAME, $csrfProtection);
        $request->cookies->set(CsrfRequestManager::CSRF_TOKEN_ID, 'test');

        $event = $this->createMock(ControllerEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $this->csrfRequestManager->expects(self::never())
            ->method(self::anything());

        $this->listener->onKernelController($event);
    }

    /**
     * @dataProvider useRequestDataProvider
     */
    public function testOnKernelControllerCsrfPassed(bool $useRequest): void
    {
        $csrfProtection = new CsrfProtection(['enabled' => true, 'useRequest' => $useRequest]);

        $request = Request::create('/');
        $request->attributes->set('_' . CsrfProtection::ALIAS_NAME, $csrfProtection);

        $event = $this->createMock(ControllerEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $this->csrfRequestManager->expects(self::once())
            ->method('refreshRequestToken')
            ->with();

        $this->csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->with($request, $useRequest)
            ->willReturn(true);

        $this->listener->onKernelController($event);
    }

    /**
     * @dataProvider useRequestDataProvider
     */
    public function testOnKernelControllerCsrfFail(bool $useRequest): void
    {
        $csrfProtection = new CsrfProtection(['enabled' => true, 'useRequest' => $useRequest]);

        $request = Request::create('/');
        $request->attributes->set('_' . CsrfProtection::ALIAS_NAME, $csrfProtection);

        $event = $this->createMock(ControllerEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $this->csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->with($request, $useRequest)
            ->willReturn(false);

        $this->csrfRequestManager->expects(self::never())
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

    public function testOnKernelResponseNotMasterRequest(): void
    {
        $event = $this->createMock(ResponseEvent::class);

        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(false);

        $event->expects(self::never())
            ->method('getResponse');

        $this->listener->onKernelResponse($event);
    }

    public function testOnKernelResponseNoCsrfProtection(): void
    {
        $request = Request::create('/');

        $event = $this->createMock(ResponseEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $event->expects(self::never())
            ->method('getResponse');

        $this->listener->onKernelResponse($event);
    }

    public function testOnKernelResponseRefreshCookie(): void
    {
        $cookie = Cookie::create('test');

        $request = Request::create('/');
        $request->attributes->set(CookieTokenStorage::CSRF_COOKIE_ATTRIBUTE, $cookie);
        $response = new Response();

        $event = $this->createMock(ResponseEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $event->expects(self::once())
            ->method('getResponse')
            ->willReturn($response);

        $this->listener->onKernelResponse($event);

        self::assertEquals([$cookie], $response->headers->getCookies());
    }
}
