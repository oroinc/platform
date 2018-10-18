<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Security\DisabledLoginSubscriber;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DisabledLoginSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var  TokenInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $token;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenStorage;

    /** @var User */
    protected $user;

    public function setUp()
    {
        $this->user = new User();
        $this->token = $this->createMock(TokenInterface::class);
        $this->token->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);

        $this->tokenStorage = $this
            ->getMockBuilder(TokenStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testOnKernelRequestWithExpiredUser()
    {
        $enum = new StubEnumValue(UserManager::STATUS_EXPIRED, UserManager::STATUS_EXPIRED);
        $this->user->setAuthStatus($enum);

        $this->tokenStorage->expects($this->once())
            ->method('setToken')
            ->with(null);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($this->token);

        $session = $this->createMock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->expects($this->once())
            ->method('set');

        $request = $this->createMock('Symfony\Component\HttpFoundation\Request');
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

    public function testOnKernelRequestWithAllowedUser()
    {
        // custom added status
        $enum = new StubEnumValue('allowed', 'allowed');
        $this->user->setAuthStatus($enum);
        $this->tokenStorage->expects($this->never())
            ->method('setToken')
            ->with(null);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($this->token);

        $session = $this->createMock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->expects($this->never())
            ->method('set');

        $request = $this->createMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->never())
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
