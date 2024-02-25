<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\SecurityBundle\Csrf\CookieTokenStorage;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\SecurityBundle\EventListener\CsrfProtectionRequestListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfProtectionRequestListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CsrfRequestManager|\PHPUnit\Framework\MockObject\MockObject */
    private $csrfRequestManager;

    /** @var CsrfTokenManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $csrfTokenManager;

    /** @var CsrfProtectionRequestListener */
    private $listener;

    protected function setUp(): void
    {
        $this->csrfRequestManager = $this->createMock(CsrfRequestManager::class);
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $this->listener = new CsrfProtectionRequestListener($this->csrfRequestManager, $this->csrfTokenManager);
    }

    private function getControllerEvent(Request $request, int $requestType): ControllerEvent
    {
        return new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            function () {
            },
            $request,
            $requestType
        );
    }

    private function getResponseEvent(Request $request, int $requestType, Response $response): ResponseEvent
    {
        return new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $requestType,
            $response
        );
    }

    public function testOnKernelControllerNotMasterRequest(): void
    {
        $event = $this->getControllerEvent(new Request(), HttpKernelInterface::SUB_REQUEST);

        $this->csrfRequestManager->expects(self::never())
            ->method(self::anything());

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerNoCsrfProtection(): void
    {
        $request = Request::create('/');
        $request->cookies->set(CsrfRequestManager::CSRF_TOKEN_ID, 'test');

        $event = $this->getControllerEvent($request, HttpKernelInterface::MAIN_REQUEST);

        $this->csrfTokenManager->expects(self::once())
            ->method('getToken')
            ->with(CsrfRequestManager::CSRF_TOKEN_ID);

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerDisabledCheck(): void
    {
        $request = Request::create('/');
        $request->attributes->set(
            '_' . CsrfProtection::ALIAS_NAME,
            new CsrfProtection(enabled: false)
        );
        $request->cookies->set(CsrfRequestManager::CSRF_TOKEN_ID, 'test');

        $event = $this->getControllerEvent($request, HttpKernelInterface::MAIN_REQUEST);

        $this->csrfTokenManager->expects(self::once())
            ->method('getToken')
            ->with(CsrfRequestManager::CSRF_TOKEN_ID);
        $this->csrfRequestManager->expects(self::never())
            ->method('refreshRequestToken');

        $this->listener->onKernelController($event);
    }

    /**
     * @dataProvider useRequestDataProvider
     */
    public function testOnKernelControllerCsrfPassed(bool $useRequest): void
    {
        $request = Request::create('/');
        $request->attributes->set(
            '_' . CsrfProtection::ALIAS_NAME,
            new CsrfProtection(enabled: true, useRequest: $useRequest)
        );

        $event = $this->getControllerEvent($request, HttpKernelInterface::MAIN_REQUEST);

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
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Invalid CSRF token');

        $request = Request::create('/');
        $request->attributes->set(
            '_' . CsrfProtection::ALIAS_NAME,
            new CsrfProtection(enabled: true, useRequest: $useRequest)
        );

        $event = $this->getControllerEvent($request, HttpKernelInterface::MAIN_REQUEST);

        $this->csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->with($request, $useRequest)
            ->willReturn(false);

        $this->csrfRequestManager->expects(self::never())
            ->method('refreshRequestToken');

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
        $response = new Response();

        $event = $this->getResponseEvent(new Request(), HttpKernelInterface::SUB_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        self::assertEmpty($response->headers->getCookies());
    }

    public function testOnKernelResponseNoCsrfProtection(): void
    {
        $request = Request::create('/');
        $response = new Response();

        $event = $this->getResponseEvent($request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        self::assertEmpty($response->headers->getCookies());
    }

    public function testOnKernelResponseRefreshCookie(): void
    {
        $cookie = Cookie::create('test');

        $request = Request::create('/');
        $request->attributes->set(CookieTokenStorage::CSRF_COOKIE_ATTRIBUTE, $cookie);
        $response = new Response();

        $event = $this->getResponseEvent($request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        self::assertEquals([$cookie], $response->headers->getCookies());
    }
}
