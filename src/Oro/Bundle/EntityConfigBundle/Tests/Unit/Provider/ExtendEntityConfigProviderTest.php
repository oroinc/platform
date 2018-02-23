<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ExtendEntityConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ExtendEntityConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExtendEntityConfigProvider */
    private $extendEntityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager */
    private $configManager;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $attributeProvider;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $enumProvider;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $extendProvider;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extendEntityConfigProvider = new ExtendEntityConfigProvider($this->configManager);

        $this->attributeProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extendProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->enumProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->exactly(3))
            ->method('getProvider')
            ->withConsecutive(['attribute'], ['extend'], ['enum'])
            ->willReturnOnConsecutiveCalls($this->attributeProvider, $this->extendProvider, $this->enumProvider);
    }

    public function testGetExtendEntityConfigs()
    {
        $configId = new EntityConfigId('extend', 'Class1');
        $extendConfig = new Config($configId, ['is_extend' => true]);
        $notExtendConfig = new Config($configId, ['is_extend' => false]);

        $this->extendProvider->expects($this->once())
            ->method('getConfigs')
            ->with(null, true)
            ->willReturn([$extendConfig, $notExtendConfig]);

        $returnedConfigs = $this->extendEntityConfigProvider->getExtendEntityConfigs();
        $this->assertSame([$extendConfig], $returnedConfigs);
    }

    public function testGetExtendEntityConfigsAttributesOnly()
    {
        $configId = new EntityConfigId('extend', 'Class1');
        $attributeExtendConfig = new Config($configId, ['is_extend' => true, 'has_attributes' => true]);
        $notExtendConfig = new Config($configId, ['is_extend' => false]);

        $this->extendProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->withConsecutive([null, true], ['Class1'])
            ->willReturnOnConsecutiveCalls([$attributeExtendConfig, $notExtendConfig], []);

        $this->attributeProvider->expects($this->once())
            ->method('getConfig')
            ->with('Class1')
            ->willReturn($attributeExtendConfig);

        $this->extendEntityConfigProvider->enableAttributesOnly();
        $returnedConfigs = $this->extendEntityConfigProvider->getExtendEntityConfigs();
        $this->assertSame([$attributeExtendConfig], $returnedConfigs);
    }

    public function testGetExtendEntityConfigsAttributesOnlyAndFilter()
    {
        $configId = new EntityConfigId('extend', 'Class1');
        $attributeExtendConfig = new Config($configId, ['is_extend' => true, 'has_attributes' => true]);
        $notAttributeConfig = new Config($configId, ['is_extend' => true, 'has_attributes' => false]);
        $filter = function () {
            //Callable filter
        };

        $this->extendProvider->expects($this->once())
            ->method('filter')
            ->with($filter, null, true)
            ->willReturn([$attributeExtendConfig, $notAttributeConfig]);

        $this->extendProvider->expects($this->once())
            ->method('getConfigs')
            ->with('Class1')
            ->willReturn([]);

        $this->attributeProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->with('Class1')
            ->willReturnOnConsecutiveCalls($attributeExtendConfig, $notAttributeConfig);

        $this->extendEntityConfigProvider->enableAttributesOnly();
        $returnedConfigs = $this->extendEntityConfigProvider->getExtendEntityConfigs($filter);
        $this->assertSame([$attributeExtendConfig], $returnedConfigs);
    }

    public function testGetExtendEntityConfigsAttributesOnlyAndEnumFields()
    {
        $configId = new EntityConfigId('extend', 'Class1');
        $attributeExtendConfig = new Config($configId, ['is_extend' => true, 'has_attributes' => true]);
        $enumExtendConfig = new Config($configId, ['is_extend' => true]);
        $notExtendConfig = new Config($configId, ['is_extend' => false]);

        $fieldConfig1 = new Config(new FieldConfigId('extend', 'Test\EnumValue1', 'field1', 'enum'));
        $fieldConfig1->set('state', ExtendScope::STATE_NEW);

        $fieldConfig2 = new Config(new FieldConfigId('extend', 'Some\Class', 'field2', 'bigint'));

        $fieldConfig3 = new Config(new FieldConfigId('extend', 'Test\EnumValue3', 'field3', 'enum'));
        $fieldConfig3->set('state', ExtendScope::STATE_NEW);

        $enumFieldConfig1 = new Config(new FieldConfigId('enum', 'Test\EnumValue1', 'field1', 'enum'));
        $enumFieldConfig1->set('enum_code', 'some_enum');

        $enumFieldConfig3 = new Config(new FieldConfigId('enum', 'Test\EnumValue3', 'field3', 'enum'));

        $this->extendProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->withConsecutive([null, true], ['Class1'])
            ->willReturnOnConsecutiveCalls(
                [$attributeExtendConfig, $notExtendConfig],
                [$fieldConfig1, $fieldConfig2, $fieldConfig3]
            );

        $this->attributeProvider->expects($this->once())
            ->method('getConfig')
            ->with('Class1')
            ->willReturn($attributeExtendConfig);

        $this->enumProvider->expects($this->at(0))
            ->method('getConfig')
            ->with('Test\EnumValue1', 'field1')
            ->willReturn($enumFieldConfig1);

        $this->enumProvider->expects($this->at(1))
            ->method('getConfig')
            ->with('Test\EnumValue3', 'field3')
            ->willReturn($enumFieldConfig3);

        $expectedEnumValueClassName = ExtendHelper::buildEnumValueClassName('some_enum');

        $this->extendProvider->expects($this->once())
            ->method('getConfig')
            ->with($expectedEnumValueClassName)
            ->willReturn($enumExtendConfig);

        $this->extendEntityConfigProvider->enableAttributesOnly();
        $returnedConfigs = $this->extendEntityConfigProvider->getExtendEntityConfigs();

        $this->assertSame([$attributeExtendConfig, $expectedEnumValueClassName => $enumExtendConfig], $returnedConfigs);
    }
}
