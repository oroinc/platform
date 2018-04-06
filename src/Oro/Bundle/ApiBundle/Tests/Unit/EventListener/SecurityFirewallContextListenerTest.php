<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\EventListener;

use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallContextListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Firewall\ContextListener;

class SecurityFirewallContextListenerTest extends \PHPUnit_Framework_TestCase
{
    private const SESSION_NAME = 'TEST_SESSION_ID';
    private const AJAX_HEADER  = 'X-CSRF-Header';

    public function testHandleShouldBeCalledWithCookie()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => 'o595fqdg5214u4e4nfcs3uc923']);
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

    public function testHandleWithExistingToken()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => 'o595fqdg5214u4e4nfcs3uc923']);
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

    public function testHandleWithNonAjaxRequest()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => 'o595fqdg5214u4e4nfcs3uc923']);

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

    public function testHandleWithoutSessionCookie()
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

    /**
     * @param string $route
     *
     * @return GetResponseEvent
     */
    private function createMasterRequestEvent($route = 'foo')
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new GetResponseEvent(
            $kernel,
            new Request([], [], ['_route' => $route]),
            HttpKernelInterface::MASTER_REQUEST
        );
    }
}
