<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Util;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;

class EnumTypeHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
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

        $config = new Config($this->getMock('Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface'));
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
