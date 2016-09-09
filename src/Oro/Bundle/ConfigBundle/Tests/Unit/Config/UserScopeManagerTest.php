<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ConfigBundle\Config\UserScopeManager;

class UserScopeManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var UserScopeManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityContext;

    protected function setUp()
    {
        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $cache    = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');

        $this->securityContext = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $this->manager = new UserScopeManager($doctrine, $cache);
        $this->manager->setSecurityContext($this->securityContext);
    }

    public function testGetScopedEntityName()
    {
        $this->assertEquals('user', $this->manager->getScopedEntityName());
    }

    public function testInitializeScopeId()
    {
        $user = new User();
        $user->setId(123);

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->assertEquals(123, $this->manager->getScopeId());
    }

    public function testInitializeScopeIdForNewUser()
    {
        $user = new User();

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->assertEquals(0, $this->manager->getScopeId());
    }

    public function testInitializeScopeIdForUnsupportedUserObject()
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn('test user');

        $this->assertEquals(0, $this->manager->getScopeId());
    }

    public function testInitializeScopeIdNoToken()
    {
        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $this->assertEquals(0, $this->manager->getScopeId());
    }

    public function testSetScopeId()
    {
        $this->securityContext->expects($this->never())
            ->method('getToken');

        $this->manager->setScopeId(456);
        $this->assertEquals(456, $this->manager->getScopeId());
    }

    public function testSetScopeIdFromEntity()
    {
        $user = new User();
        $user->setId(123);

        $this->manager->setScopeIdFromEntity($user);
        $this->assertEquals(123, $this->manager->getScopeId());
    }
}
