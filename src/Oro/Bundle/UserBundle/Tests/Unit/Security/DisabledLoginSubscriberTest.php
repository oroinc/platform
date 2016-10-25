<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\DisabledLoginSubscriber;

class DisabledLoginSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $tokenStorage;

    public function setUp()
    {
        $user = new User();
        $user->setLoginDisabled(true);

        $this->token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage = $this
            ->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testOnKernelRequest()
    {
        $this->tokenStorage->expects($this->once())
            ->method('setToken')
            ->with(null);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($this->token);

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->expects($this->once())
            ->method('set');

        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())
            ->method('hasSession')
            ->willReturn(true);
        $request->expects($this->any())
            ->method('getSession')
            ->willReturn($session);

        $getResponseEvent = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $getResponseEvent->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $disabledLoginSubscriber = new DisabledLoginSubscriber($this->tokenStorage);
        $disabledLoginSubscriber->onKernelRequest($getResponseEvent);
    }
}
