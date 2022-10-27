<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Audit;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Audit\AuditManager;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuditManagerTest extends \PHPUnit\Framework\TestCase
{
    private const SCOPE = 'testScope';
    private const ENTITY_CLASS = 'Test\Entity';

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var AuditManager */
    private $auditManager;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManager')
            ->with(null)
            ->willReturn($this->em);

        $this->auditManager = new AuditManager($this->tokenStorage, $doctrine);
    }

    public function testBuildEntity()
    {
        $user = $this->initSecurityContext();

        $this->configManager->expects($this->once())
            ->method('getUpdateConfig')
            ->willReturn(
                [
                    new Config(new EntityConfigId(self::SCOPE, self::ENTITY_CLASS)),
                    new Config(new FieldConfigId(self::SCOPE, self::ENTITY_CLASS, 'testField', 'string')),
                ]
            );

        $configProvider = new ConfigProvider($this->configManager, self::SCOPE, new PropertyConfigBag([]));
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturn($configProvider);

        $this->configManager->expects($this->exactly(2))
            ->method('getConfigChangeSet')
            ->willReturn(['old_val', 'new_value']);

        $result = $this->auditManager->buildEntity($this->configManager);

        $this->assertSame($user, $result->getUser());
        $this->assertCount(2, $result->getDiffs());
    }

    public function testBuildEntityNoChanges()
    {
        $this->initSecurityContext();

        $this->configManager->expects($this->once())
            ->method('getUpdateConfig')
            ->willReturn([]);

        $this->assertNull($this->auditManager->buildEntity($this->configManager));
    }

    public function testBuildEntityWithoutSecurityToken()
    {
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn(null);
        $this->configManager->expects($this->never())
            ->method('getUpdateConfig');

        $this->auditManager->buildEntity($this->configManager);
    }

    public function testBuildEntityWithUnsupportedSecurityToken()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn('test');
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);
        $this->configManager->expects($this->never())
            ->method('getUpdateConfig');

        $this->auditManager->buildEntity($this->configManager);
    }

    private function initSecurityContext(): UserInterface
    {
        $user = $this->createMock(UserInterface::class);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->identicalTo($user))
            ->willReturn(['id' => 123]);
        $this->em->expects($this->once())
            ->method('getReference')
            ->with(get_class($user), 123)
            ->willReturn($user);

        return $user;
    }
}
