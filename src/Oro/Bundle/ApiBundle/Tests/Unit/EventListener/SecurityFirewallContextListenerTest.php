<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\EventListener;

use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallContextListener;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
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

    /**
     * @param ContextListener       $innerListener
     * @param TokenStorageInterface $tokenStorage
     *
     * @return SecurityFirewallContextListener
     */
    private function getListener(ContextListener $innerListener, TokenStorageInterface $tokenStorage)
    {
        return new SecurityFirewallContextListener(
            $innerListener,
            ['name' => self::SESSION_NAME],
            $tokenStorage
        );
    }

    public function testShouldCallInnerHandleIfNoTokenAndHasSessionCookieAndAjaxHeader()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);
        $innerListener->expects(self::once())
            ->method('handle')
            ->with($event);
        $csrfRequestManager->expects($this->once())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false)
            ->willReturn(true);


        $listener = $this->getListener($innerListener, $tokenStorage);
        $listener->setCsrfRequestManager($csrfRequestManager);

        $listener->handle($event);
    }

    public function testShouldNotCallInnerHandleIfTokenExists()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(new UsernamePasswordToken('user', 'password', 'test'));
        $innerListener->expects(self::never())
            ->method('handle');


        $listener = $this->getListener($innerListener, $tokenStorage);
        $listener->setCsrfRequestManager($csrfRequestManager);

        $listener->handle($event);
    }

    public function testShouldNonCallInnerHandleIfNoTokenAndHasSessionCookieButNoAjaxHeader()
    {
        $event = $this->createMasterRequestEvent(false);
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(new UsernamePasswordToken('user', 'password', 'test'));
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = $this->getListener($innerListener, $tokenStorage);
        $listener->setCsrfRequestManager($csrfRequestManager);
        $listener->handle($event);
    }

    public function testHandleWithoutToken()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = $this->getListener($innerListener, $tokenStorage);
        $listener->setCsrfRequestManager($csrfRequestManager);
        $listener->handle($event);
    }

    public function testShouldNonCallInnerHandleIfNoTokenAndHasAjaxHeaderButNoSessionCookie()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = $this->getListener($innerListener, $tokenStorage);
        $listener->setCsrfRequestManager($csrfRequestManager);
        $listener->handle($event);
    }

    public function testShouldCallInnerHandleForAnonymousTokenAndHasSessionCookieAndAjaxHeader()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = new TokenStorage();
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);
        $csrfRequestManager->expects($this->once())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false)
            ->willReturn(true);

        $anonymousToken = $this->createMock(AnonymousToken::class);
        $sessionToken = $this->createMock(TokenInterface::class);
        $tokenStorage->setToken($anonymousToken);

        $innerListener->expects(self::once())
            ->method('handle')
            ->with($event)
            ->willReturnCallback(function (GetResponseEvent $event) use ($tokenStorage, $sessionToken) {
                $tokenStorage->setToken($sessionToken);
            });

        $listener = $this->getListener($innerListener, $tokenStorage);
        $listener->setCsrfRequestManager($csrfRequestManager);
        $listener->handle($event);

        self::assertSame($sessionToken, $tokenStorage->getToken());
    }

    public function testShouldKeepOriginalAnonymousTokenIfInnerHandlerSetNullToken()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = new TokenStorage();
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);
        $csrfRequestManager->expects($this->once())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false)
            ->willReturn(true);

        $anonymousToken = $this->createMock(AnonymousToken::class);
        $tokenStorage->setToken($anonymousToken);

        $innerListener->expects(self::once())
            ->method('handle')
            ->with($event)
            ->willReturnCallback(function (GetResponseEvent $event) use ($tokenStorage) {
                $tokenStorage->setToken(null);
            });

        $listener = $this->getListener($innerListener, $tokenStorage);
        $listener->setCsrfRequestManager($csrfRequestManager);
        $listener->handle($event);

        self::assertSame($anonymousToken, $tokenStorage->getToken());
    }

    public function testShouldNonCallInnerHandleForAnonymousTokenAndHasSessionCookieButNoAjaxHeader()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);
        $csrfRequestManager->expects($this->once())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false)
            ->willReturn(false);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(AnonymousToken::class));
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = $this->getListener($innerListener, $tokenStorage);
        $listener->setCsrfRequestManager($csrfRequestManager);
        $listener->handle($event);
    }

    public function testShouldNonCallInnerHandleForAnonymousTokenAndHasAjaxHeaderButNoSessionCookie()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(ContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(AnonymousToken::class));
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = $this->getListener($innerListener, $tokenStorage);
        $listener->handle($event);
    }

    /**
     * @param bool $isXmlHttpRequest
     *
     * @return GetResponseEvent
     */
    private function createMasterRequestEvent($isXmlHttpRequest = true)
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request([], [], ['_route' => 'foo']);
        if ($isXmlHttpRequest) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }

        return new GetResponseEvent(
            $kernel,
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );
    }
}
