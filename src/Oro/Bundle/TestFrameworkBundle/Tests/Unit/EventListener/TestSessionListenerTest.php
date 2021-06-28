<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\EventListener;

use Oro\Bundle\TestFrameworkBundle\EventListener\TestSessionListener;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class TestSessionListenerTest extends \PHPUnit\Framework\TestCase
{
    private RequestEvent|\PHPUnit\Framework\MockObject\MockObject $event;

    private ContainerInterface|\PHPUnit\Framework\MockObject\MockObject $container;

    private TestSessionListener $testSessionListener;

    protected function setUp(): void
    {
        $this->event = $this->createMock(RequestEvent::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->testSessionListener = new TestSessionListener($this->container);
    }

    public function testListenerNoSession(): void
    {
        $this->container->expects(self::once())
            ->method('has')
            ->with('session')
            ->willReturn(false);
        $this->container->expects(self::never())
            ->method('get');
        $this->testSessionListener->onKernelRequest($this->event);
    }

    public function testListenerSameSessionId(): void
    {
        $sessionId = 'sessionId';
        $sessionName = 'sessionName';
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects(self::once())
            ->method('getId')
            ->willReturn($sessionId);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn($sessionName);

        $request = $this->createMock(Request::class);
        $request->cookies = $this->createMock(ParameterBag::class);
        $request->cookies->expects(self::once())
            ->method('get')
            ->with($sessionName)
            ->willReturn($sessionId);

        $this->event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $this->container->expects(self::once())
            ->method('has')
            ->with('session')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('session')
            ->willReturn($session);

        $this->event->expects(self::never())
            ->method('isMasterRequest');

        $this->testSessionListener->onKernelRequest($this->event);
    }

    public function testSetSession(): void
    {
        $sessionId = 'sessionId';
        $sessionName = 'sessionName';
        $newSessionId = 'newSessionId';
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects(self::once())
            ->method('getId')
            ->willReturn($sessionId);
        $session->expects(self::exactly(3))
            ->method('getName')
            ->willReturn($sessionName);
        $session->expects(self::once())
            ->method('setId')
            ->with($newSessionId);

        $request = $this->createMock(Request::class);
        $request->cookies = $this->createMock(ParameterBag::class);
        $request->cookies->expects(self::exactly(2))
            ->method('get')
            ->with($sessionName)
            ->willReturn($newSessionId);
        $request->cookies->expects(self::once())
            ->method('has')
            ->with($sessionName)
            ->willReturn(true);

        $this->event->expects(self::exactly(2))
            ->method('getRequest')
            ->willReturn($request);
        $this->event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $this->container->expects(self::exactly(2))
            ->method('has')
            ->with('session')
            ->willReturn(true);
        $this->container->expects(self::exactly(2))
            ->method('get')
            ->with('session')
            ->willReturn($session);

        $this->testSessionListener->onKernelRequest($this->event);
    }
}
