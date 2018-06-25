<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Util;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumTypeHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var EnumTypeHelper */
    protected $typeHelper;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->typeHelper = new EnumTypeHelper($this->configManager);
    }

    public function testGetEnumCodeForEntityNoConfig()
    {
        $className = 'Test\Entity';

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(false));

        $this->assertNull(
            $this->typeHelper->getEnumCode($className)
        );
    }

    public function testGetEnumCodeForFieldNoConfig()
    {
        $className = 'Test\Entity';
        $fieldName = 'testField';

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(false));

        $this->assertNull(
            $this->typeHelper->getEnumCode($className, $fieldName)
        );
    }

    public function testGetEnumCodeForEntity()
    {
        $enumCode  = 'test_enum';
        $className = 'Test\Entity';

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('code')
            ->will($this->returnValue($enumCode));

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(true));
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->will($this->returnValue($config));

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

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('enum_code')
            ->will($this->returnValue($enumCode));

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(true));
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($config));

        $this->assertEquals(
            $enumCode,
            $this->typeHelper->getEnumCode($className, $fieldName)
        );
    }

    public function testHasEnumCodeForEntityNoConfig()
    {
        $className = 'Test\Entity';

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(false));

        $this->assertFalse(
            $this->typeHelper->hasEnumCode($className)
        );
    }

    public function testHasEnumCodeForFieldNoConfig()
    {
        $className = 'Test\Entity';
        $fieldName = 'testField';

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(false));

        $this->assertFalse(
            $this->typeHelper->hasEnumCode($className, $fieldName)
        );
    }

    public function testHasEnumCodeForEntity()
    {
        $enumCode  = 'test_enum';
        $className = 'Test\Entity';

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('code')
            ->will($this->returnValue($enumCode));

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(true));
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->will($this->returnValue($config));

        $this->assertTrue(
            $this->typeHelper->hasEnumCode($className)
        );
    }

    public function testHasEnumCodeForField()
    {
        $enumCode  = 'test_enum';
        $className = 'Test\Entity';
        $fieldName = 'testField';

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('enum_code')
            ->will($this->returnValue($enumCode));

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(true));
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($config));

        $this->assertTrue(
            $this->typeHelper->hasEnumCode($className, $fieldName)
        );
    }

    public function testHasEnumCodeForEmptyEnumCode()
    {
        $enumCode  = '';
        $className = 'Test\Entity';
        $fieldName = 'testField';

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('enum_code')
            ->will($this->returnValue($enumCode));

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(true));
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($config));

        $this->assertFalse(
            $this->typeHelper->hasEnumCode($className, $fieldName)
        );
    }

    /**
     * @dataProvider hasOtherReferencesProvider
     */
    public function testHasOtherReferences($enumType)
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

        $extendConfigProvider = $this->getConfigProviderMock();
        $enumConfigProvider   = $this->getConfigProviderMock();
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['enum', $enumConfigProvider],
                        ['extend', $extendConfigProvider]
                    ]
                )
            );
        $extendConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue($configs));
        $enumConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with('Test\Entity1')
            ->will($this->returnValue($fieldConfigs));

        $this->assertTrue(
            $this->typeHelper->hasOtherReferences($enumCode, 'Test\Entity1', 'field1')
        );
    }

    /**
     * @dataProvider hasOtherReferencesProvider
     */
    public function testHasOtherReferencesWithNoRefs($enumType)
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

        $extendConfigProvider = $this->getConfigProviderMock();
        $enumConfigProvider   = $this->getConfigProviderMock();
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['enum', $enumConfigProvider],
                        ['extend', $extendConfigProvider]
                    ]
                )
            );
        $extendConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue($configs));
        $enumConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with('Test\Entity1')
            ->will($this->returnValue($fieldConfigs));

        $this->assertFalse(
            $this->typeHelper->hasOtherReferences($enumCode, 'Test\Entity1', 'field1')
        );
    }

    public function hasOtherReferencesProvider()
    {
        return [
            ['enum'],
            ['multiEnum'],
        ];
    }

    public function testIsSystemNoConfig()
    {
        $className = 'Test\Entity';

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(false));
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

        $config = new Config($this->createMock('Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface'));
        $config->set('owner', $owner);

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(true));
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($config));

        $this->assertEquals(
            $expected,
            $this->typeHelper->isSystem($className, $fieldName)
        );
    }

    public function isSystemProvider()
    {
        return [
            [ExtendScope::OWNER_SYSTEM, true, null],
            [ExtendScope::OWNER_CUSTOM, false, null],
            [ExtendScope::OWNER_SYSTEM, true, 'testField'],
            [ExtendScope::OWNER_CUSTOM, false, 'testField'],
        ];
    }

    public function testGetPublicEnumTypes()
    {
        $config1 = new Config(new EntityConfigId('extend', 'Test\EnumValue1'));
        $config1->set('is_extend', true);
        $config1->set('inherit', ExtendHelper::BASE_ENUM_VALUE_CLASS);
        $config1->set('state', ExtendScope::STATE_ACTIVE);
        $config2 = new Config(new EntityConfigId('extend', 'Test\EnumValue2'));
        $config2->set('is_extend', true);
        $config2->set('inherit', ExtendHelper::BASE_ENUM_VALUE_CLASS);
        $config2->set('state', ExtendScope::STATE_UPDATE);
        $config3 = new Config(new EntityConfigId('extend', 'Test\EnumValue2'));
        $config3->set('is_extend', true);
        $config3->set('inherit', ExtendHelper::BASE_ENUM_VALUE_CLASS);
        $config3->set('state', ExtendScope::STATE_NEW);
        $config4 = new Config(new EntityConfigId('extend', 'Test\EnumValue2'));
        $config4->set('is_extend', true);
        $config4->set('inherit', ExtendHelper::BASE_ENUM_VALUE_CLASS);
        $config4->set('state', ExtendScope::STATE_DELETE);
        $config5 = new Config(new EntityConfigId('extend', 'Test\EnumValue1'));
        $config5->set('is_extend', true);
        $config5->set('inherit', ExtendHelper::BASE_ENUM_VALUE_CLASS);
        $config5->set('state', ExtendScope::STATE_ACTIVE);
        $config6 = new Config(new EntityConfigId('extend', 'Test\Entity6'));
        $config7 = new Config(new EntityConfigId('extend', 'Test\EnumValue2'));
        $config7->set('is_extend', true);
        $config7->set('inherit', ExtendHelper::BASE_ENUM_VALUE_CLASS);
        $config7->set('state', ExtendScope::STATE_ACTIVE);
        $config7->set('is_deleted', true);
        $config8 = new Config(new EntityConfigId('extend', 'Test\EnumValue8'));
        $config8->set('is_extend', true);
        $config8->set('inherit', ExtendHelper::BASE_ENUM_VALUE_CLASS);
        $config8->set('state', ExtendScope::STATE_ACTIVE);

        $configs = [$config1, $config2, $config3, $config4, $config5, $config6, $config7, $config8];

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

        $extendConfigProvider = $this->getConfigProviderMock();
        $enumConfigProvider   = $this->getConfigProviderMock();
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['enum', $enumConfigProvider],
                        ['extend', $extendConfigProvider]
                    ]
                )
            );
        $extendConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with(null, true)
            ->will($this->returnValue($configs));
        $enumConfigProvider->expects($this->exactly(4))
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        ['Test\EnumValue1', null, $enumConfig1],
                        ['Test\EnumValue2', null, $enumConfig2],
                        ['Test\EnumValue5', null, $enumConfig5],
                        ['Test\EnumValue8', null, $enumConfig8],
                    ]
                )
            );

        $this->assertEquals(
            [
                'test_enum1' => 'multiEnum||test_enum1',
                'test_enum2' => 'enum||test_enum2'
            ],
            $this->typeHelper->getPublicEnumTypes()
        );
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
