<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\UserScopeManager;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserScopeManagerTest extends AbstractScopeManagerTestCase
{
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $securityContext;

    #[\Override]
    protected function setUp(): void
    {
        $this->securityContext = $this->createMock(TokenStorageInterface::class);
        parent::setUp();
    }

    #[\Override]
    protected function createManager(): UserScopeManager
    {
        $manager = new UserScopeManager($this->doctrine, $this->cache, $this->dispatcher, $this->configBag);
        $manager->setSecurityContext($this->securityContext);

        return $manager;
    }

    #[\Override]
    protected function getScopedEntityName(): string
    {
        return 'user';
    }

    #[\Override]
    protected function getScopedEntity(): User
    {
        $entity = new User();
        $entity->setId(123);

        return $entity;
    }

    public function testInitializeScopeId(): void
    {
        $user = $this->getScopedEntity();

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->securityContext->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertSame($user->getId(), $this->manager->getScopeId());
    }

    public function testInitializeScopeIdForNewUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn(new User());
        $this->securityContext->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertSame(0, $this->manager->getScopeId());
    }

    public function testInitializeScopeIdForUnsupportedUserObject(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn(new User());
        $this->securityContext->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertSame(0, $this->manager->getScopeId());
    }

    public function testInitializeScopeIdNoToken(): void
    {
        $this->securityContext->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        self::assertSame(0, $this->manager->getScopeId());
    }

    public function testGetScopeIdFromEntityForUserWithEmptyId(): void
    {
        self::assertSame(0, $this->manager->getScopeIdFromEntity(new User()));
    }

    public function testDeleteScope(): void
    {
        $scopeId = 101;

        $config = new Config();
        $config->getValues()->add($this->getConfigValue('oro_user', 'update', 'scalar', 'old value'));

        $this->repo->expects(self::once())
            ->method('findByEntity')
            ->with($this->getScopedEntityName(), $scopeId)
            ->willReturn($config);

        $this->manager->deleteScope($scopeId);

        self::assertEquals(
            ['oro_user.update' => [ConfigManager::USE_PARENT_SCOPE_VALUE_KEY => true]],
            $this->manager->getChanges($scopeId)
        );
    }
}
