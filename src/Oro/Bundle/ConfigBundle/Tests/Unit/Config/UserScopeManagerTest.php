<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\UserScopeManager;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Event\ConfigManagerScopeIdUpdateEvent;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Cache\CacheInterface;

class UserScopeManagerTest extends AbstractScopeManagerTestCase
{
    /** @var UserScopeManager */
    protected $manager;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $securityContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->securityContext = $this->createMock(TokenStorageInterface::class);

        $this->manager->setSecurityContext($this->securityContext);
    }

    public function testInitializeScopeId()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($this->getScopedEntity());

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals(123, $this->manager->getScopeId());
    }

    public function testInitializeScopeIdForNewUser()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(new User());

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals(0, $this->manager->getScopeId());
    }

    public function testInitializeScopeIdForUnsupportedUserObject()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn('test user');

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

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

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::anything(), ConfigManagerScopeIdUpdateEvent::EVENT_NAME);

        $this->manager->setScopeId(456);

        $this->assertEquals(456, $this->manager->getScopeId());
    }

    /**
     * {@inheritdoc}
     */
    protected function createManager(
        ManagerRegistry $doctrine,
        CacheInterface $cache,
        EventDispatcher $eventDispatcher,
        ConfigBag $configBag,
    ): UserScopeManager {
        return new UserScopeManager($doctrine, $cache, $eventDispatcher, $configBag);
    }

    /**
     * {@inheritdoc}
     */
    protected function getScopedEntityName(): string
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    protected function getScopedEntity(): User
    {
        return $this->getEntity(User::class, ['id' => 123]);
    }

    public function testDeleteScope()
    {
        $configValue1 = new ConfigValue();
        $configValue1->setSection('oro_user')->setName('update')->setValue('old value')->setType('scalar');

        $config = new Config();
        $config->getValues()->add($configValue1);

        $this->repo->expects($this->once())
            ->method('findByEntity')
            ->with($this->getScopedEntityName(), 101)
            ->willReturn($config);

        $this->manager->deleteScope(101);
        self::assertEquals(
            ['oro_user.update' => [ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => true]],
            $this->manager->getChanges(101)
        );
    }
}
