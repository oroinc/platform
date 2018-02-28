<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\EventListener;

use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallContextListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Firewall\ContextListener;

class SecurityFirewallContextListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testHandleShouldBeCalledWithCookie()
    {
        $sessionOptions = ['name' => 'OROID'];

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add(['OROID' => 'o595fqdg5214u4e4nfcs3uc923']);
        $event->getRequest()->headers->add(['X-CSRF-Header' => true]);

        /** @var ContextListener|\PHPUnit_Framework_MockObject_MockObject $innerListener */
        $innerListener = $this->getMockBuilder('Symfony\Component\Security\Http\Firewall\ContextListener')
            ->disableOriginalConstructor()
            ->getMock();
        $tokenStorage = $this
            ->createMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $innerListener
            ->expects($this->once())
            ->method('handle')
            ->with($event);

        $listener = new SecurityFirewallContextListener($innerListener, $sessionOptions, $tokenStorage);
        $listener->handle($event);
    }

    public function testHandleWithExistingToken()
    {
        $sessionOptions = ['name' => 'OROID'];
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add(['OROID' => 'o595fqdg5214u4e4nfcs3uc923']);
        $event->getRequest()->headers->add(['X-CSRF-Header' => true]);

        /** @var ContextListener|\PHPUnit_Framework_MockObject_MockObject $innerListener */
        $innerListener = $this->getMockBuilder('Symfony\Component\Security\Http\Firewall\ContextListener')
            ->disableOriginalConstructor()
            ->getMock();
        $tokenStorage = $this
            ->createMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(new UsernamePasswordToken('user', 'password', 'test'));

        $innerListener
            ->expects($this->never())
            ->method('handle');

        $listener = new SecurityFirewallContextListener($innerListener, $sessionOptions, $tokenStorage);
        $listener->handle($event);
    }

    public function testHandleWithNonAjaxRequest()
    {
        $sessionOptions = ['name' => 'OROID'];
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add(['OROID' => 'o595fqdg5214u4e4nfcs3uc923']);

        /** @var ContextListener|\PHPUnit_Framework_MockObject_MockObject $innerListener */
        $innerListener = $this->getMockBuilder('Symfony\Component\Security\Http\Firewall\ContextListener')
            ->disableOriginalConstructor()
            ->getMock();
        $tokenStorage = $this
            ->createMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $innerListener
            ->expects($this->never())
            ->method('handle');

        $listener = new SecurityFirewallContextListener($innerListener, $sessionOptions, $tokenStorage);
        $listener->handle($event);
    }

    public function testHandleWithoutSessionCookie()
    {
        $sessionOptions = ['name' => 'OROID'];
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->headers->add(['X-CSRF-Header' => true]);

        /** @var ContextListener|\PHPUnit_Framework_MockObject_MockObject $innerListener */
        $innerListener = $this->getMockBuilder('Symfony\Component\Security\Http\Firewall\ContextListener')
            ->disableOriginalConstructor()
            ->getMock();
        $tokenStorage = $this
            ->createMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $innerListener
            ->expects($this->never())
            ->method('handle');

        $listener = new SecurityFirewallContextListener($innerListener, $sessionOptions, $tokenStorage);
        $listener->handle($event);
    }

    /**
     * @param string $route
     *
     * @return GetResponseEvent
     */
    protected function createMasterRequestEvent($route = 'foo')
    {
        /** @var HttpKernelInterface|\PHPUnit_Framework_MockObject_MockObject $kernel */
        $kernel = $this->createMock('Symfony\Component\HttpKernel\HttpKernelInterface');

        return new GetResponseEvent(
            $kernel,
            new Request([], [], ['_route' => $route]),
            HttpKernelInterface::MASTER_REQUEST
        );
    }
}
