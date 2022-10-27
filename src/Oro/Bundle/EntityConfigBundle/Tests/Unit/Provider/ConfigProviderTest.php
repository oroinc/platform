<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity as TestEntity;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_SCOPE = 'testScope';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var PropertyConfigBag|\PHPUnit\Framework\MockObject\MockObject */
    private $configBag;

    /** @var ConfigProvider */
    private $configProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configBag = $this->createMock(PropertyConfigBag::class);

        $this->configProvider = new ConfigProvider($this->configManager, self::TEST_SCOPE, $this->configBag);
    }

    public function testGetPropertyConfig()
    {
        $propertyConfig = $this->createMock(PropertyConfigContainer::class);

        $this->configBag->expects($this->once())
            ->method('getPropertyConfig')
            ->with(self::TEST_SCOPE)
            ->willReturn($propertyConfig);

        $this->assertSame($propertyConfig, $this->configProvider->getPropertyConfig());
    }

    public function testGetConfigManager()
    {
        $this->assertSame($this->configManager, $this->configProvider->getConfigManager());
    }

    public function testGetIdForNullClassName()
    {
        $this->assertEquals(
            new EntityConfigId(self::TEST_SCOPE),
            $this->configProvider->getId()
        );
    }

    public function testGetIdForEntityConfig()
    {
        $this->assertEquals(
            new EntityConfigId(self::TEST_SCOPE, TestEntity::class),
            $this->configProvider->getId(TestEntity::class)
        );
    }

    public function testGetIdForFieldConfig()
    {
        $this->assertEquals(
            new FieldConfigId(self::TEST_SCOPE, TestEntity::class, 'testField', 'int'),
            $this->configProvider->getId(TestEntity::class, 'testField', 'int')
        );
    }

    public function testGetIdForFieldConfigWithoutFieldType()
    {
        $fieldConfig = new FieldConfigId(self::TEST_SCOPE, TestEntity::class, 'testField', 'int');

        $this->configManager->expects($this->once())
            ->method('getId')
            ->with(self::TEST_SCOPE, TestEntity::class, 'testField')
            ->willReturn($fieldConfig);

        $this->assertSame(
            $fieldConfig,
            $this->configProvider->getId(TestEntity::class, 'testField')
        );
    }

    public function testHasConfigForEntity()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(TestEntity::class, null)
            ->willReturn(true);

        $this->assertTrue(
            $this->configProvider->hasConfig(TestEntity::class)
        );
    }

    public function testHasConfigForField()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(TestEntity::class, 'testField')
            ->willReturn(true);

        $this->assertTrue(
            $this->configProvider->hasConfig(TestEntity::class, 'testField')
        );
    }

    public function testHasConfigByIdForEntity()
    {
        $configId = new EntityConfigId(self::TEST_SCOPE, TestEntity::class);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(TestEntity::class, null)
            ->willReturn(true);

        $this->assertTrue(
            $this->configProvider->hasConfigById($configId)
        );
    }

    public function testHasConfigByIdForField()
    {
        $configId = new FieldConfigId(self::TEST_SCOPE, TestEntity::class, 'testField', 'string');

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(TestEntity::class, 'testField')
            ->willReturn(true);

        $this->assertTrue(
            $this->configProvider->hasConfigById($configId)
        );
    }

    public function testGetConfigForNullClassName()
    {
        $entityConfig = new Config(
            new EntityConfigId(self::TEST_SCOPE)
        );

        $this->configManager->expects($this->once())
            ->method('createEntityConfig')
            ->with(self::TEST_SCOPE)
            ->willReturn($entityConfig);

        $this->assertSame(
            $entityConfig,
            $this->configProvider->getConfig()
        );
    }

    public function testGetConfigForEntity()
    {
        $entityConfig = new Config(
            new EntityConfigId(self::TEST_SCOPE, TestEntity::class)
        );

        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with(self::TEST_SCOPE, TestEntity::class)
            ->willReturn($entityConfig);

        $this->assertSame(
            $entityConfig,
            $this->configProvider->getConfig(TestEntity::class)
        );
    }

    public function testGetConfigForField()
    {
        $fieldConfig = new Config(
            new FieldConfigId(self::TEST_SCOPE, TestEntity::class, 'testField', 'int')
        );

        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with(self::TEST_SCOPE, TestEntity::class, 'testField')
            ->willReturn($fieldConfig);

        $this->assertSame(
            $fieldConfig,
            $this->configProvider->getConfig(TestEntity::class, 'testField')
        );
    }

    public function testGetConfigByIdForNullClassName()
    {
        $configId = new EntityConfigId(self::TEST_SCOPE);
        $entityConfig = new Config($configId);

        $this->configManager->expects($this->once())
            ->method('createEntityConfig')
            ->with(self::TEST_SCOPE)
            ->willReturn($entityConfig);

        $this->assertSame(
            $entityConfig,
            $this->configProvider->getConfigById($configId)
        );
    }

    public function testGetConfigByIdForEntity()
    {
        $configId = new EntityConfigId(self::TEST_SCOPE, TestEntity::class);
        $entityConfig = new Config($configId);

        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with(self::TEST_SCOPE, TestEntity::class)
            ->willReturn($entityConfig);

        $this->assertSame(
            $entityConfig,
            $this->configProvider->getConfigById($configId)
        );
    }

    public function testGetConfigByIdForField()
    {
        $configId = new FieldConfigId(self::TEST_SCOPE, TestEntity::class, 'testField', 'int');
        $fieldConfig = new Config($configId);

        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with(self::TEST_SCOPE, TestEntity::class, 'testField')
            ->willReturn($fieldConfig);

        $this->assertSame(
            $fieldConfig,
            $this->configProvider->getConfigById($configId)
        );
    }

    public function testGetIdsForEntities()
    {
        $entityConfigId = new EntityConfigId(self::TEST_SCOPE, TestEntity::class);

        $this->configManager->expects($this->once())
            ->method('getIds')
            ->with(self::TEST_SCOPE, null, false)
            ->willReturn([$entityConfigId]);

        $this->assertEquals(
            [$entityConfigId],
            $this->configProvider->getIds()
        );
    }

    public function testGetIdsForFields()
    {
        $fieldConfigId = new FieldConfigId(self::TEST_SCOPE, TestEntity::class, 'testField', 'int');

        $this->configManager->expects($this->once())
            ->method('getIds')
            ->with(self::TEST_SCOPE, TestEntity::class, false)
            ->willReturn([$fieldConfigId]);

        $this->assertEquals(
            [$fieldConfigId],
            $this->configProvider->getIds(TestEntity::class)
        );
    }

    public function testGetIdsForEntitiesIncludingHidden()
    {
        $entityConfigId = new EntityConfigId(self::TEST_SCOPE, TestEntity::class);

        $this->configManager->expects($this->once())
            ->method('getIds')
            ->with(self::TEST_SCOPE, null, true)
            ->willReturn([$entityConfigId]);

        $this->assertEquals(
            [$entityConfigId],
            $this->configProvider->getIds(null, true)
        );
    }

    public function testGetIdsForFieldsIncludingHidden()
    {
        $fieldConfigId = new FieldConfigId(self::TEST_SCOPE, TestEntity::class, 'testField', 'int');

        $this->configManager->expects($this->once())
            ->method('getIds')
            ->with(self::TEST_SCOPE, TestEntity::class, true)
            ->willReturn([$fieldConfigId]);

        $this->assertEquals(
            [$fieldConfigId],
            $this->configProvider->getIds(TestEntity::class, true)
        );
    }

    public function testGetConfigsForEntities()
    {
        $entityConfig = new Config(
            new EntityConfigId(self::TEST_SCOPE, TestEntity::class)
        );

        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with(self::TEST_SCOPE, null, false)
            ->willReturn([$entityConfig]);

        $this->assertEquals(
            [$entityConfig],
            $this->configProvider->getConfigs()
        );
    }

    public function testGetConfigsForFields()
    {
        $fieldConfig = new Config(
            new FieldConfigId(self::TEST_SCOPE, TestEntity::class, 'testField', 'int')
        );

        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with(self::TEST_SCOPE, TestEntity::class, false)
            ->willReturn([$fieldConfig]);

        $this->assertEquals(
            [$fieldConfig],
            $this->configProvider->getConfigs(TestEntity::class)
        );
    }

    public function testGetConfigsForEntitiesIncludingHidden()
    {
        $entityConfig = new Config(
            new EntityConfigId(self::TEST_SCOPE, TestEntity::class)
        );

        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with(self::TEST_SCOPE, null, true)
            ->willReturn([$entityConfig]);

        $this->assertEquals(
            [$entityConfig],
            $this->configProvider->getConfigs(null, true)
        );
    }

    public function testGetConfigsForFieldsIncludingHidden()
    {
        $fieldConfig = new Config(
            new FieldConfigId(self::TEST_SCOPE, TestEntity::class, 'testField', 'int')
        );

        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with(self::TEST_SCOPE, TestEntity::class, true)
            ->willReturn([$fieldConfig]);

        $this->assertEquals(
            [$fieldConfig],
            $this->configProvider->getConfigs(TestEntity::class, true)
        );
    }

    public function testMap()
    {
        $entityConfig = new Config(new EntityConfigId(self::TEST_SCOPE, TestEntity::class));

        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with(self::TEST_SCOPE, TestEntity::class, false)
            ->willReturn([$entityConfig]);

        $expectedEntityConfig = new Config(new EntityConfigId(self::TEST_SCOPE, TestEntity::class));
        $expectedEntityConfig->set('key', 'value');

        $result = $this->configProvider->map(
            function (ConfigInterface $config) {
                return $config->set('key', 'value');
            },
            TestEntity::class
        );
        $this->assertEquals([$expectedEntityConfig], $result);
    }

    public function testFilter()
    {
        $entityConfig = new Config(new EntityConfigId(self::TEST_SCOPE, TestEntity::class));

        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with(self::TEST_SCOPE, TestEntity::class, false)
            ->willReturn([$entityConfig]);

        $result = $this->configProvider->filter(
            function (ConfigInterface $config) {
                return $config->getId()->getScope() === 'wrongScope';
            },
            TestEntity::class
        );
        $this->assertEquals([], $result);
    }

    public function testGetScope()
    {
        $this->assertEquals(self::TEST_SCOPE, $this->configProvider->getScope());
    }
}
