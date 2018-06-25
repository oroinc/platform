<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\UserScopeManager;
use Oro\Bundle\ConfigBundle\Event\ConfigManagerScopeIdUpdateEvent;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserScopeManagerTest extends AbstractScopeManagerTestCase
{
    /** @var UserScopeManager */
    protected $manager;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $securityContext;

    protected function setUp()
    {
        parent::setUp();

        $this->securityContext = $this->createMock(TokenStorageInterface::class);

        $this->manager->setSecurityContext($this->securityContext);
    }

    public function testInitializeScopeId()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn($this->getScopedEntity());

        $this->securityContext->expects($this->once())->method('getToken')->willReturn($token);

        $this->assertEquals(123, $this->manager->getScopeId());
    }

    public function testInitializeScopeIdForNewUser()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn(new User());

        $this->securityContext->expects($this->once())->method('getToken')->willReturn($token);

        $this->assertEquals(0, $this->manager->getScopeId());
    }

    public function testInitializeScopeIdForUnsupportedUserObject()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn('test user');

        $this->securityContext->expects($this->once())->method('getToken')->willReturn($token);

        $this->assertEquals(0, $this->manager->getScopeId());
    }

    public function testInitializeScopeIdNoToken()
    {
        $this->securityContext->expects($this->once())->method('getToken')->willReturn(null);

        $this->assertEquals(0, $this->manager->getScopeId());
    }

    public function testSetScopeId()
    {
        $this->securityContext->expects($this->never())->method('getToken');

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ConfigManagerScopeIdUpdateEvent::EVENT_NAME);

        $this->manager->setScopeId(456);

        $this->assertEquals(456, $this->manager->getScopeId());
    }

    /**
     * {@inheritdoc}
     *
     * @return UserScopeManager
     */
    protected function createManager(ManagerRegistry $doctrine, CacheProvider $cache, EventDispatcher $eventDispatcher)
    {
        return new UserScopeManager($doctrine, $cache, $eventDispatcher);
    }

    /**
     * {@inheritdoc}
     */
    protected function getScopedEntityName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     *
     * @return User
     */
    protected function getScopedEntity()
    {
        return $this->getEntity(User::class, ['id' => 123]);
    }
}
