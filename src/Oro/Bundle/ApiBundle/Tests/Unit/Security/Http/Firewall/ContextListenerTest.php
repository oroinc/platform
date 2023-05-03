<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Security\Http\Firewall;

use Oro\Bundle\ApiBundle\Security\Http\Firewall\ContextListener;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\SecurityBundle\Request\CsrfProtectedRequestHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Firewall\ContextListener as BaseContextListener;

class ContextListenerTest extends \PHPUnit\Framework\TestCase
{
    private const SESSION_NAME = 'TEST_SESSION_ID';
    private const SESSION_ID = 'o595fqdg5214u4e4nfcs3uc923';

    /** @var BaseContextListener|\PHPUnit\Framework\MockObject\MockObject */
    private $innerListener;

    protected function setUp(): void
    {
        $this->innerListener = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();
    }

    private function getListener(
        object $innerListener,
        TokenStorageInterface $tokenStorage,
        ?CsrfRequestManager $csrfRequestManager
    ): ContextListener {
        $listener = new ContextListener(
            $innerListener,
            $tokenStorage
        );

        if ($csrfRequestManager) {
            $listener->setCsrfRequestManager($csrfRequestManager);
            $listener->setCsrfProtectedRequestHelper(new CsrfProtectedRequestHelper($csrfRequestManager));
        }

        return $listener;
    }

    private function createMasterRequestEvent($isXmlHttpRequest = true): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request([], [], ['_route' => 'foo']);
        if ($isXmlHttpRequest) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }

        return new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    public function testShouldCallInnerHandleIfNoTokenAndHasSessionCookieAndAjaxHeader(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setSession($session);
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);
        $this->innerListener->expects(self::once())
            ->method('__invoke')
            ->with($event);
        $csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false)
            ->willReturn(true);

        $listener = $this->getListener($this->innerListener, $tokenStorage, $csrfRequestManager);
        $listener($event);
    }

    public function testShouldNotCallInnerHandleIfTokenExists(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::never())
            ->method('getName');

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setSession($session);
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(new UsernamePasswordToken('user', 'password', 'test'));
        $this->innerListener->expects(self::never())
            ->method('__invoke');

        $listener = $this->getListener($this->innerListener, $tokenStorage, $csrfRequestManager);
        $listener($event);
    }

    public function testShouldNonCallInnerHandleIfNoTokenAndHasSessionCookieButNoAjaxHeader(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $event = $this->createMasterRequestEvent(false);
        $event->getRequest()->setSession($session);
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);
        $this->innerListener->expects(self::never())
            ->method('__invoke');

        $listener = $this->getListener($this->innerListener, $tokenStorage, $csrfRequestManager);
        $listener($event);
    }

    public function testShouldNonCallInnerHandleIfNoTokenAndHasAjaxHeaderButNoSessionCookie(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setSession($session);
        $event->getRequest()->setMethod('POST');

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);
        $this->innerListener->expects(self::never())
            ->method('__invoke');

        $listener = $this->getListener($this->innerListener, $tokenStorage, $csrfRequestManager);
        $listener($event);
    }

    public function testShouldCallInnerHandleForAnonymousTokenAndHasSessionCookieAndAjaxHeader(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setSession($session);
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $tokenStorage = new TokenStorage();
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);
        $csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false)
            ->willReturn(true);

        $anonymousToken = $this->createMock(AnonymousToken::class);
        $sessionToken = $this->createMock(TokenInterface::class);
        $tokenStorage->setToken($anonymousToken);

        $this->innerListener->expects(self::once())
            ->method('__invoke')
            ->with($event)
            ->willReturnCallback(static fn (RequestEvent $event) => $tokenStorage->setToken($sessionToken));

        $listener = $this->getListener($this->innerListener, $tokenStorage, $csrfRequestManager);
        $listener($event);

        self::assertSame($sessionToken, $tokenStorage->getToken());
    }

    public function testShouldKeepOriginalAnonymousTokenIfInnerHandlerSetNullToken(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setSession($session);
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $tokenStorage = new TokenStorage();
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);
        $csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false)
            ->willReturn(true);

        $anonymousToken = $this->createMock(AnonymousToken::class);
        $tokenStorage->setToken($anonymousToken);

        $this->innerListener->expects(self::once())
            ->method('__invoke')
            ->with($event)
            ->willReturnCallback(static fn (RequestEvent $event) => $tokenStorage->setToken(null));

        $listener = $this->getListener($this->innerListener, $tokenStorage, $csrfRequestManager);
        $listener($event);

        self::assertSame($anonymousToken, $tokenStorage->getToken());
    }

    public function testShouldNonCallInnerHandleForAnonymousTokenAndHasSessionCookieButNoAjaxHeader(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setSession($session);
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);
        $csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false)
            ->willReturn(false);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(AnonymousToken::class));
        $this->innerListener->expects(self::never())
            ->method('__invoke');

        $listener = $this->getListener($this->innerListener, $tokenStorage, $csrfRequestManager);
        $listener($event);
    }

    public function testShouldNonCallInnerHandleForAnonymousTokenAndHasAjaxHeaderButNoSessionCookie(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setSession($session);
        $event->getRequest()->setMethod('POST');

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(AnonymousToken::class));
        $this->innerListener->expects(self::never())
            ->method('__invoke');

        $listener = $this->getListener($this->innerListener, $tokenStorage, $csrfRequestManager);
        $listener($event);
    }
}
