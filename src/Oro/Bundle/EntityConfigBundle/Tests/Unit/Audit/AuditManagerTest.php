<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Audit;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\EntityConfigBundle\Audit\AuditManager;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class AuditManagerTest extends \PHPUnit_Framework_TestCase
{
    const SCOPE = 'testScope';
    const ENTITY_CLASS = 'Test\Entity';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $tokenStorage;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $em;

    /** @var AuditManager */
    private $auditManager;

    protected function setUp()
    {
        $this->tokenStorage = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->any())
            ->method('getManager')
            ->with(null)
            ->willReturn($this->em);

        $this->auditManager = new AuditManager($this->tokenStorage, $doctrine);
    }

    protected function tearDown()
    {
        unset($this->tokenStorage, $this->configManager, $this->auditManager);
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

        $configProvider = new ConfigProvider($this->configManager, self::SCOPE, []);
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
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
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

    /**
     * @return UserInterface
     */
    protected function initSecurityContext()
    {
        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
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
