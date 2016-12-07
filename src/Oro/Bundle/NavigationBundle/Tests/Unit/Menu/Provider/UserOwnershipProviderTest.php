<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\NavigationBundle\Menu\Provider\UserOwnershipProvider;
use Oro\Bundle\UserBundle\Entity\User;

class UserOwnershipProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserOwnershipProvider
     */
    private $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    private $tokenStorage;

    public function setUp()
    {
        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenStorage = $this->getMock(TokenStorageInterface::class);

        $this->provider = new UserOwnershipProvider($registry, '\EntityClass', $this->tokenStorage);
    }

    public function testGetType()
    {
        $this->assertEquals('user', $this->provider->getType());
    }

    public function testGetIdWithEmptyToken()
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);
        $this->assertEquals(null, $this->provider->getId());
    }

    public function testGetIdWithDifferentUserClass()
    {
        $user = new \stdClass();
        $token = $this->getMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->assertEquals(null, $this->provider->getId());
    }

    public function testGetId()
    {
        $userId = 26;
        $user = $this->getMock(User::class);
        $user->expects($this->any())
            ->method('getId')
            ->willReturn($userId);
        $token = $this->getMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->assertEquals($userId, $this->provider->getId());
    }
}
