<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Http\Firewall\ContextListener;

use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallContextListener;

class SecurityFirewallContextListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testHandleShouldBeCalledWithCookie()
    {
        $sessionOptions = ['name' => 'OROID'];

        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add(['OROID' => 'o595fqdg5214u4e4nfcs3uc923']);

        /** @var ContextListener|\PHPUnit_Framework_MockObject_MockObject $innerListener */
        $innerListener = $this->getMockBuilder('Symfony\Component\Security\Http\Firewall\ContextListener')
            ->disableOriginalConstructor()
            ->getMock();
        $innerListener
            ->expects($this->once())
            ->method('handle')
            ->with($event);

        $listener = new SecurityFirewallContextListener($innerListener, $sessionOptions);
        $listener->handle($event);
    }

    public function testHandleShouldNotBeCalledWithoutCookie()
    {
        $sessionOptions = ['name' => 'OROID'];

        $event = $this->createMasterRequestEvent();

        /** @var ContextListener|\PHPUnit_Framework_MockObject_MockObject $innerListener */
        $innerListener = $this->getMockBuilder('Symfony\Component\Security\Http\Firewall\ContextListener')
            ->disableOriginalConstructor()
            ->getMock();
        $innerListener
            ->expects($this->never())
            ->method('handle');

        $listener = new SecurityFirewallContextListener($innerListener, $sessionOptions);
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
        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');

        return new GetResponseEvent(
            $kernel,
            new Request([], [], ['_route' => $route]),
            HttpKernelInterface::MASTER_REQUEST
        );
    }
}
