<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\EventListener;

use Oro\Bundle\TestFrameworkBundle\EventListener\TestSessionListener;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class TestSessionListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GetResponseEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $event;

    /**
     * @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $container;

    /**
     * @var TestSessionListener|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $testSessionListener;

    public function setUp()
    {
        $this->event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockBuilder(ContainerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->testSessionListener = new TestSessionListener($this->container);
    }

    public function testListenerNoSession()
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with('session')
            ->willReturn(false);
        $this->container->expects($this->never())
            ->method('get');
        $this->testSessionListener->onKernelRequest($this->event);
    }

    public function testListenerSameSessionId()
    {
        $sessionId = 'sessionId';
        $sessionName = 'sessionName';
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects($this->once())
            ->method('getId')
            ->willReturn($sessionId);
        $session->expects($this->once())
            ->method('getName')
            ->willReturn($sessionName);

        $request = $this->createMock(Request::class);
        $request->cookies = $this->createMock(ParameterBag::class);
        $request->cookies->expects($this->once())
            ->method('get')
            ->with($sessionName)
            ->willReturn($sessionId);

        $this->event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->container->expects($this->once())
            ->method('has')
            ->with('session')
            ->willReturn(true);
        $this->container->expects($this->once())
            ->method('get')
            ->with('session')
            ->willReturn($session);

        $this->event->expects($this->never())
            ->method('isMasterRequest');

        $this->testSessionListener->onKernelRequest($this->event);
    }

    public function testSetSession()
    {
        $sessionId = 'sessionId';
        $sessionName = 'sessionName';
        $newSessionId = 'newSessionId';
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects($this->once())
            ->method('getId')
            ->willReturn($sessionId);
        $session->expects($this->exactly(3))
            ->method('getName')
            ->willReturn($sessionName);
        $session->expects($this->once())
            ->method('setId')
            ->with($newSessionId);

        $request = $this->createMock(Request::class);
        $request->cookies = $this->createMock(ParameterBag::class);
        $request->cookies->expects($this->exactly(2))
            ->method('get')
            ->with($sessionName)
            ->willReturn($newSessionId);
        $request->cookies->expects($this->once())
            ->method('has')
            ->with($sessionName)
            ->willReturn(true);

        $this->event->expects($this->exactly(2))
            ->method('getRequest')
            ->willReturn($request);
        $this->event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $this->container->expects($this->exactly(2))
            ->method('has')
            ->with('session')
            ->willReturn(true);
        $this->container->expects($this->exactly(2))
            ->method('get')
            ->with('session')
            ->willReturn($session);

        $this->testSessionListener->onKernelRequest($this->event);
    }
}
