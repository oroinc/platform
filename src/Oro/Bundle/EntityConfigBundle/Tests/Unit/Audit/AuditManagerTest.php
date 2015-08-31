<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Audit;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Audit\AuditManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class AuditManagerTest extends \PHPUnit_Framework_TestCase
{
    const SCOPE = 'testScope';
    const ENTITY_CLASS = 'Test\Entity';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $tokenStorage;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

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

        $this->auditManager = new AuditManager($this->tokenStorage);
    }

    protected function tearDown()
    {
        unset($this->tokenStorage, $this->configManager, $this->auditManager);
    }

    public function testBuildLogEntry()
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

        $result = $this->auditManager->buildLogEntry($this->configManager);

        $this->assertSame($user, $result->getUser());
        $this->assertCount(2, $result->getDiffs());
    }

    public function testBuildLogEntryNoChanges()
    {
        $this->initSecurityContext();

        $this->configManager->expects($this->once())
            ->method('getUpdateConfig')
            ->willReturn([]);

        $this->assertNull($this->auditManager->buildLogEntry($this->configManager));
    }

    public function testBuildLogEntryWithoutSecurityToken()
    {
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn(null);
        $this->configManager->expects($this->never())
            ->method('getUpdateConfig');

        $this->auditManager->buildLogEntry($this->configManager);
    }

    public function testBuildLogEntryWithUnsupportedSecurityToken()
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

        $this->auditManager->buildLogEntry($this->configManager);
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

        return $user;
    }
}
