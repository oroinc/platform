<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Util;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EnumTypeHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EnumTypeHelper */
    private $typeHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->typeHelper = new EnumTypeHelper($this->configManager);
    }

    public function testGetEnumCodeForEntityNoConfig()
    {
        $className = 'Test\Entity';

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($configProvider);
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(false);

        $this->assertNull(
            $this->typeHelper->getEnumCode($className)
        );
    }

    public function testGetEnumCodeForFieldNoConfig()
    {
        $className = 'Test\Entity';
        $fieldName = 'testField';

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($configProvider);
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->willReturn(false);

        $this->assertNull(
            $this->typeHelper->getEnumCode($className, $fieldName)
        );
    }

    public function testGetEnumCodeForEntity()
    {
        $enumCode  = 'test_enum';
        $className = 'Test\Entity';

        $config = $this->createMock(Config::class);
        $config->expects($this->once())
            ->method('get')
            ->with('code')
            ->willReturn($enumCode);

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($configProvider);
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($config);

        $this->assertEquals(
            $enumCode,
            $this->typeHelper->getEnumCode($className)
        );
    }

    public function testGetEnumCodeForField()
    {
        $enumCode  = 'test_enum';
        $className = 'Test\Entity';
        $fieldName = 'testField';

        $config = $this->createMock(Config::class);
        $config->expects($this->once())
            ->method('get')
            ->with('enum_code')
            ->willReturn($enumCode);

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($configProvider);
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->willReturn(true);
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($config);

        $this->assertEquals(
            $enumCode,
            $this->typeHelper->getEnumCode($className, $fieldName)
        );
    }

    public function testHasEnumCodeForEntityNoConfig()
    {
        $className = 'Test\Entity';

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($configProvider);
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(false);

        $this->assertFalse(
            $this->typeHelper->hasEnumCode($className)
        );
    }

    public function testHasEnumCodeForFieldNoConfig()
    {
        $className = 'Test\Entity';
        $fieldName = 'testField';

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($configProvider);
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->willReturn(false);

        $this->assertFalse(
            $this->typeHelper->hasEnumCode($className, $fieldName)
        );
    }

    public function testHasEnumCodeForEntity()
    {
        $enumCode = 'test_enum';
        $className = 'Test\Entity';

        $config = $this->createMock(Config::class);
        $config->expects($this->once())
            ->method('get')
            ->with('code')
            ->willReturn($enumCode);

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($configProvider);
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($config);

        $this->assertTrue(
            $this->typeHelper->hasEnumCode($className)
        );
    }

    public function testHasEnumCodeForField()
    {
        $enumCode = 'test_enum';
        $className = 'Test\Entity';
        $fieldName = 'testField';

        $config = $this->createMock(Config::class);
        $config->expects($this->once())
            ->method('get')
            ->with('enum_code')
            ->willReturn($enumCode);

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($configProvider);
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->willReturn(true);
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($config);

        $this->assertTrue(
            $this->typeHelper->hasEnumCode($className, $fieldName)
        );
    }

    public function testHasEnumCodeForEmptyEnumCode()
    {
        $enumCode = '';
        $className = 'Test\Entity';
        $fieldName = 'testField';

        $config = $this->createMock(Config::class);
        $config->expects($this->once())
            ->method('get')
            ->with('enum_code')
            ->willReturn($enumCode);

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($configProvider);
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->willReturn(true);
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($config);

        $this->assertFalse(
            $this->typeHelper->hasEnumCode($className, $fieldName)
        );
    }

    /**
     * @dataProvider hasOtherReferencesProvider
     */
    public function testHasOtherReferences(string $enumType)
    {
        $enumCode = 'test_enum';

        $config1 = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $config1->set('is_extend', true);
        $config2 = new Config(new EntityConfigId('extend', 'Test\Entity2'));

        $config1Field1 = new Config(new FieldConfigId('enum', 'Test\Entity1', 'field1', $enumType));
        $config1Field1->set('enum_code', $enumCode);
        $config1Field2 = new Config(new FieldConfigId('enum', 'Test\Entity1', 'field2', $enumType));
        $config1Field2->set('enum_code', $enumCode);
        $config1Field3 = new Config(new FieldConfigId('enum', 'Test\Entity1', 'field3', 'string'));

        $configs      = [$config1, $config2];
        $fieldConfigs = [$config1Field1, $config1Field2, $config1Field3];

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->willReturnMap([
                ['enum', $enumConfigProvider],
                ['extend', $extendConfigProvider]
            ]);
        $extendConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->willReturn($configs);
        $enumConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with('Test\Entity1')
            ->willReturn($fieldConfigs);

        $this->assertTrue(
            $this->typeHelper->hasOtherReferences($enumCode, 'Test\Entity1', 'field1')
        );
    }

    /**
     * @dataProvider hasOtherReferencesProvider
     */
    public function testHasOtherReferencesWithNoRefs(string $enumType)
    {
        $enumCode = 'test_enum';

        $config1 = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $config1->set('is_extend', true);
        $config2 = new Config(new EntityConfigId('extend', 'Test\Entity2'));

        $config1Field1 = new Config(new FieldConfigId('enum', 'Test\Entity1', 'field1', $enumType));
        $config1Field1->set('enum_code', $enumCode);
        $config1Field2 = new Config(new FieldConfigId('enum', 'Test\Entity1', 'field2', $enumType));
        $config1Field2->set('enum_code', 'another_enum');

        $configs      = [$config1, $config2];
        $fieldConfigs = [$config1Field1, $config1Field2];

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->willReturnMap([
                ['enum', $enumConfigProvider],
                ['extend', $extendConfigProvider]
            ]);
        $extendConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->willReturn($configs);
        $enumConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with('Test\Entity1')
            ->willReturn($fieldConfigs);

        $this->assertFalse(
            $this->typeHelper->hasOtherReferences($enumCode, 'Test\Entity1', 'field1')
        );
    }

    public function hasOtherReferencesProvider(): array
    {
        return [
            ['enum'],
            ['multiEnum'],
        ];
    }

    public function testIsSystemNoConfig()
    {
        $className = 'Test\Entity';

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($configProvider);
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(false);
        $configProvider->expects($this->never())
            ->method('getConfig');

        $this->assertFalse(
            $this->typeHelper->isSystem($className)
        );
    }

    /**
     * @dataProvider isSystemProvider
     */
    public function testIsSystem($owner, $expected, $fieldName = null)
    {
        $className = 'Test\Entity';

        $config = new Config($this->createMock(ConfigIdInterface::class));
        $config->set('owner', $owner);

        $configProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($configProvider);
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->willReturn(true);
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($config);

        $this->assertEquals(
            $expected,
            $this->typeHelper->isSystem($className, $fieldName)
        );
    }

    public function isSystemProvider(): array
    {
        return [
            [ExtendScope::OWNER_SYSTEM, true, null],
            [ExtendScope::OWNER_CUSTOM, false, null],
            [ExtendScope::OWNER_SYSTEM, true, 'testField'],
            [ExtendScope::OWNER_CUSTOM, false, 'testField'],
        ];
    }

    public function testGetUndefinedEnumFieldType()
    {
        $enumConfig1 = new Config(new EntityConfigId('enum', 'Test\EnumValue1'));
        $enumConfig1->set('public', true);
        $enumConfig1->set('code', 'test_enum1');
        $enumConfig1->set('multiple', true);
        $enumConfig2 = new Config(new EntityConfigId('enum', 'Test\EnumValue2'));
        $enumConfig2->set('public', true);
        $enumConfig2->set('code', 'test_enum2');
        $enumConfig5 = new Config(new EntityConfigId('enum', 'Test\EnumValue3'));
        $enumConfig5->set('code', 'test_enum5');
        $enumConfig8 = new Config(new EntityConfigId('enum', 'Test\EnumValue8'));
        $enumConfig8->set('code', 'test_enum8');

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->exactly(1))
            ->method('getProvider')
            ->willReturnMap([
                ['enum', $enumConfigProvider],
                ['extend', $extendConfigProvider]
            ]);

        $this->assertEquals(
            null,
            $this->typeHelper->getEnumFieldType('Test\EnumValue1', 'undefined_field')
        );
    }
}
