<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Oro\Bundle\NavigationBundle\Event\AddMasterRequestRouteListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AddMasterRequestRouteListenerTest extends \PHPUnit\Framework\TestCase
{
    private AddMasterRequestRouteListener $listener;

    protected function setUp(): void
    {
        $this->listener = new AddMasterRequestRouteListener();
    }

    public function testOnKernelRequest(): void
    {
        $route = 'foo';

        $masterRequestEvent = $this->createMasterRequestEvent($route);

        $this->listener->onKernelRequest($masterRequestEvent);
        self::assertTrue($masterRequestEvent->getRequest()->attributes->has('_master_request_route'));
        self::assertEquals($route, $masterRequestEvent->getRequest()->attributes->get('_master_request_route'));

        $subRequestEvent = $this->createSubRequestEvent($route);
        $this->listener->onKernelRequest($subRequestEvent);
        self::assertTrue($subRequestEvent->getRequest()->attributes->has('_master_request_route'));
        self::assertEquals($route, $subRequestEvent->getRequest()->attributes->get('_master_request_route'));
    }

    protected function createMasterRequestEvent(string $route): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent(
            $kernel,
            new Request([], [], ['_route' => $route]),
            HttpKernelInterface::MASTER_REQUEST
        );
    }

    private function createSubRequestEvent(): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent(
            $kernel,
            new Request(),
            HttpKernelInterface::SUB_REQUEST
        );
    }
}
