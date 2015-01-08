<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Oro\Bundle\NavigationBundle\Event\AddMasterRequestRouteListener;

class AddMasterRequestRouteListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AddMasterRequestRouteListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new AddMasterRequestRouteListener();
    }

    public function testOnKernelRequest()
    {
        $route = 'foo';

        $masterRequestEvent = $this->createMasterRequestEvent($route);

        $this->listener->onKernelRequest($masterRequestEvent);
        $this->assertTrue($masterRequestEvent->getRequest()->attributes->has('_master_request_route'));
        $this->assertEquals($route, $masterRequestEvent->getRequest()->attributes->get('_master_request_route'));

        $subRequestEvent = $this->createSubRequestEvent($route);
        $this->listener->onKernelRequest($subRequestEvent);
        $this->assertTrue($subRequestEvent->getRequest()->attributes->has('_master_request_route'));
        $this->assertEquals($route, $subRequestEvent->getRequest()->attributes->get('_master_request_route'));
    }

    /**
     * @param string $route
     * @return GetResponseEvent
     */
    protected function createMasterRequestEvent($route)
    {
        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');

        return new GetResponseEvent(
            $kernel,
            new Request([], [], ['_route' => $route]),
            HttpKernelInterface::MASTER_REQUEST
        );
    }

    /**
     * @return GetResponseEvent
     */
    protected function createSubRequestEvent()
    {
        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');

        return new GetResponseEvent(
            $kernel,
            new Request(),
            HttpKernelInterface::SUB_REQUEST
        );
    }
}
