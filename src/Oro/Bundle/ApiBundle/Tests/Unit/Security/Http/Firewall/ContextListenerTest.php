<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Security\Http\Firewall;

use Oro\Bundle\ApiBundle\Security\Http\Firewall\ContextListener;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
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
    private const SESSION_ID   = 'o595fqdg5214u4e4nfcs3uc923';

    /**
     * @param BaseContextListener     $innerListener
     * @param TokenStorageInterface   $tokenStorage
     * @param SessionInterface|null   $session
     * @param CsrfRequestManager|null $csrfRequestManager
     *
     * @return ContextListener
     */
    private function getListener(
        BaseContextListener $innerListener,
        TokenStorageInterface $tokenStorage,
        ?SessionInterface $session,
        ?CsrfRequestManager $csrfRequestManager
    ) {
        $listener = new ContextListener(
            $innerListener,
            $tokenStorage,
            $session
        );

        if ($csrfRequestManager) {
            $listener->setCsrfRequestManager($csrfRequestManager);
        }

        return $listener;
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

    public function testShouldCallInnerHandleIfNoTokenAndHasSessionCookieAndAjaxHeader()
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setSession($session);
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(BaseContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);
        $innerListener->expects(self::once())
            ->method('handle')
            ->with($event);
        $csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false)
            ->willReturn(true);

        $listener = $this->getListener($innerListener, $tokenStorage, $session, $csrfRequestManager);
        $listener->handle($event);
    }

    public function testShouldNotCallInnerHandleIfTokenExists()
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::never())
            ->method('getName');

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setSession($session);
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(BaseContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(new UsernamePasswordToken('user', 'password', 'test'));
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = $this->getListener($innerListener, $tokenStorage, $session, $csrfRequestManager);
        $listener->handle($event);
    }

    public function testShouldNonCallInnerHandleIfNoTokenAndHasSessionCookieButNoAjaxHeader()
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $event = $this->createMasterRequestEvent(false);
        $event->getRequest()->setSession($session);
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(BaseContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = $this->getListener($innerListener, $tokenStorage, $session, $csrfRequestManager);
        $listener->handle($event);
    }

    public function testShouldNonCallInnerHandleIfNoTokenAndHasAjaxHeaderButNoSessionCookie()
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setSession($session);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(BaseContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = $this->getListener($innerListener, $tokenStorage, $session, $csrfRequestManager);
        $listener->handle($event);
    }

    public function testShouldCallInnerHandleForAnonymousTokenAndHasSessionCookieAndAjaxHeader()
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setSession($session);
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(BaseContextListener::class);
        $tokenStorage = new TokenStorage();
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);
        $csrfRequestManager->expects(self::once())
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

        $listener = $this->getListener($innerListener, $tokenStorage, $session, $csrfRequestManager);
        $listener->handle($event);

        self::assertSame($sessionToken, $tokenStorage->getToken());
    }

    public function testShouldKeepOriginalAnonymousTokenIfInnerHandlerSetNullToken()
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setSession($session);
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(BaseContextListener::class);
        $tokenStorage = new TokenStorage();
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);
        $csrfRequestManager->expects(self::once())
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

        $listener = $this->getListener($innerListener, $tokenStorage, $session, $csrfRequestManager);
        $listener->handle($event);

        self::assertSame($anonymousToken, $tokenStorage->getToken());
    }

    public function testShouldNonCallInnerHandleForAnonymousTokenAndHasSessionCookieButNoAjaxHeader()
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setSession($session);
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(BaseContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);
        $csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false)
            ->willReturn(false);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(AnonymousToken::class));
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = $this->getListener($innerListener, $tokenStorage, $session, $csrfRequestManager);
        $listener->handle($event);
    }

    public function testShouldNonCallInnerHandleForAnonymousTokenAndHasAjaxHeaderButNoSessionCookie()
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->setSession($session);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(BaseContextListener::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(AnonymousToken::class));
        $innerListener->expects(self::never())
            ->method('handle');

        $listener = $this->getListener($innerListener, $tokenStorage, $session, $csrfRequestManager);
        $listener->handle($event);
    }
}
