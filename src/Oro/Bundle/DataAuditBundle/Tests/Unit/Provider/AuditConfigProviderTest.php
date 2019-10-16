<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Provider;

use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

class AuditConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var AuditConfigProvider */
    private $provider;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new AuditConfigProvider($this->configManager);

        parent::setUp();
    }

    public function testIsAuditableEntityWhenEnum(): void
    {
        $this->assertTrue($this->provider->isAuditableEntity(StubEnumValue::class));
    }

    public function testIsAuditableEntityWhenNoConfig(): void
    {
        $this->configManager
            ->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass = \stdClass::class)
            ->willReturn(false);

        $this->assertFalse($this->provider->isAuditableEntity($entityClass));
    }

    public function testIsAuditableEntityWhenNotAuditable(): void
    {
        $this->configManager
            ->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass = \stdClass::class)
            ->willReturn(true);

        $this->configManager
            ->expects($this->once())
            ->method('getEntityConfig')
            ->with(AuditConfigProvider::DATA_AUDIT_SCOPE, $entityClass)
            ->willReturn($config = $this->createMock(ConfigInterface::class));

        $config
            ->expects($this->once())
            ->method('is')
            ->with('auditable')
            ->willReturn(false);

        $this->assertFalse($this->provider->isAuditableEntity($entityClass));
    }

    public function testIsAuditableEntityWhenAuditable(): void
    {
        $this->configManager
            ->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass = \stdClass::class)
            ->willReturn(true);

        $this->configManager
            ->expects($this->once())
            ->method('getEntityConfig')
            ->with(AuditConfigProvider::DATA_AUDIT_SCOPE, $entityClass)
            ->willReturn($config = $this->createMock(ConfigInterface::class));

        $config
            ->expects($this->once())
            ->method('is')
            ->with('auditable')
            ->willReturn(true);

        $this->assertTrue($this->provider->isAuditableEntity($entityClass));
    }

    public function testIsAuditableFieldWhenNoConfig(): void
    {
        $this->configManager
            ->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass = \stdClass::class, $fieldName = 'sampleField')
            ->willReturn(false);

        $this->assertFalse($this->provider->isAuditableField($entityClass, $fieldName));
    }

    public function testIsAuditableFieldWhenNotAuditable(): void
    {
        $this->configManager
            ->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass = \stdClass::class, $fieldName = 'sampleField')
            ->willReturn(true);

        $this->configManager
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with(AuditConfigProvider::DATA_AUDIT_SCOPE, $entityClass, $fieldName)
            ->willReturn($config = $this->createMock(ConfigInterface::class));

        $config
            ->expects($this->once())
            ->method('is')
            ->with('auditable')
            ->willReturn(false);

        $this->assertFalse($this->provider->isAuditableField($entityClass, $fieldName));
    }

    public function testIsAuditableFieldWhenAuditable(): void
    {
        $this->configManager
            ->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass = \stdClass::class, $fieldName = 'sampleField')
            ->willReturn(true);

        $this->configManager
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with(AuditConfigProvider::DATA_AUDIT_SCOPE, $entityClass, $fieldName)
            ->willReturn($config = $this->createMock(ConfigInterface::class));

        $config
            ->expects($this->once())
            ->method('is')
            ->with('auditable')
            ->willReturn(true);

        $this->assertTrue($this->provider->isAuditableField($entityClass, $fieldName));
    }

    public function testGetAllAuditableEntities(): void
    {
        $auditableConfig = $this->createMock(ConfigInterface::class);
        $auditableConfig
            ->expects($this->once())
            ->method('is')
            ->with('auditable')
            ->willReturn(true);

        $auditableConfig
            ->expects($this->once())
            ->method('getId')
            ->willReturn($configId = $this->createMock(ConfigIdInterface::class));

        $configId
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn($className = 'sample-class');

        $notAuditableConfig = $this->createMock(ConfigInterface::class);
        $notAuditableConfig
            ->expects($this->once())
            ->method('is')
            ->with('auditable')
            ->willReturn(false);

        $this->configManager
            ->expects($this->once())
            ->method('getConfigs')
            ->with(AuditConfigProvider::DATA_AUDIT_SCOPE, null, true)
            ->willReturn($configs = [$auditableConfig, $notAuditableConfig]);

        $this->assertEquals([$className], $this->provider->getAllAuditableEntities());
    }
}
