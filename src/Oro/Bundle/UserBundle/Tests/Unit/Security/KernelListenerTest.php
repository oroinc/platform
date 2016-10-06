<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\KernelListener;

class KernelListenerTest extends \PHPUnit_Framework_TestCase
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
        $request->expects($this->any())
            ->method('getSession')
            ->willReturn($session);

        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $container = $this->getMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['security.token_storage', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->tokenStorage],
                    ['request_stack', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $requestStack]
                ]
            );

        $getResponseEvent = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $kernelListener = new KernelListener($container);
        $kernelListener->onKernelRequest($getResponseEvent);
    }
}
