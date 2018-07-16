<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\EventListener;

use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallContextListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Firewall\ContextListener;

class SecurityFirewallContextListenerTest extends \PHPUnit\Framework\TestCase
{
    private const SESSION_NAME = 'TEST_SESSION_ID';
    private const SESSION_ID   = 'o595fqdg5214u4e4nfcs3uc923';
    private const AJAX_HEADER  = 'X-CSRF-Header';

    /**
     * @return GetResponseEvent
     */
    private function createMasterRequestEvent()
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new GetResponseEvent(
            $kernel,
            new Request([], [], ['_route' => 'foo']),
            HttpKernelInterface::MASTER_REQUEST
        );
    }

    public function testShouldCallInnerHandleIfNoTokenAndHasSessionCookieAndAjaxHeader()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->headers->add([self::AJAX_HEADER => true]);

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);
        $innerListener->expects(self::once())
            ->method('handle')
            ->with($event);

        $listener = new SecurityFirewallContextListener($innerListener, self::SESSION_NAME, $tokenStorage);
        $listener->handle($event);
    }

    public function testShouldNotCallInnerHandleIfTokenExists()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->headers->add([self::AJAX_HEADER => true]);

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(new UsernamePasswordToken('user', 'password', 'test'));
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = new SecurityFirewallContextListener($innerListener, self::SESSION_NAME, $tokenStorage);
        $listener->handle($event);
    }

    public function testShouldNonCallInnerHandleIfNoTokenAndHasSessionCookieButNoAjaxHeader()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = new SecurityFirewallContextListener($innerListener, self::SESSION_NAME, $tokenStorage);
        $listener->handle($event);
    }

    public function testShouldNonCallInnerHandleIfNoTokenAndHasAjaxHeaderButNoSessionCookie()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->headers->add([self::AJAX_HEADER => true]);

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = new SecurityFirewallContextListener($innerListener, self::SESSION_NAME, $tokenStorage);
        $listener->handle($event);
    }

    public function testShouldCallInnerHandleForAnonymousTokenAndHasSessionCookieAndAjaxHeader()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->headers->add([self::AJAX_HEADER => true]);

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = new TokenStorage();

        $anonymousToken = $this->createMock(AnonymousToken::class);
        $sessionToken = $this->createMock(TokenInterface::class);
        $tokenStorage->setToken($anonymousToken);

        $innerListener->expects(self::once())
            ->method('handle')
            ->with($event)
            ->willReturnCallback(function (GetResponseEvent $event) use ($tokenStorage, $sessionToken) {
                $tokenStorage->setToken($sessionToken);
            });

        $listener = new SecurityFirewallContextListener($innerListener, self::SESSION_NAME, $tokenStorage);
        $listener->handle($event);

        self::assertSame($sessionToken, $tokenStorage->getToken());
    }

    public function testShouldKeepOriginalAnonymousTokenIfInnerHandlerSetNullToken()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->headers->add([self::AJAX_HEADER => true]);

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = new TokenStorage();

        $anonymousToken = $this->createMock(AnonymousToken::class);
        $tokenStorage->setToken($anonymousToken);

        $innerListener->expects(self::once())
            ->method('handle')
            ->with($event)
            ->willReturnCallback(function (GetResponseEvent $event) use ($tokenStorage) {
                $tokenStorage->setToken(null);
            });

        $listener = new SecurityFirewallContextListener($innerListener, self::SESSION_NAME, $tokenStorage);
        $listener->handle($event);

        self::assertSame($anonymousToken, $tokenStorage->getToken());
    }

    public function testShouldNonCallInnerHandleForAnonymousTokenAndHasSessionCookieButNoAjaxHeader()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(AnonymousToken::class));
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = new SecurityFirewallContextListener($innerListener, self::SESSION_NAME, $tokenStorage);
        $listener->handle($event);
    }

    public function testShouldNonCallInnerHandleForAnonymousTokenAndHasAjaxHeaderButNoSessionCookie()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->headers->add([self::AJAX_HEADER => true]);

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(AnonymousToken::class));
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = new SecurityFirewallContextListener($innerListener, self::SESSION_NAME, $tokenStorage);
        $listener->handle($event);
    }
}
