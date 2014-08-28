<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Util;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;

class ConfigTypeHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var ConfigTypeHelper */
    protected $typeHelper;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->typeHelper = new ConfigTypeHelper($this->configManager);
    }

    /**
     * @dataProvider getFieldNameProvider
     */
    public function testGetFieldName($configId, $expected)
    {
        $this->assertSame(
            $expected,
            $this->typeHelper->getFieldName($configId)
        );
    }

    public function getFieldNameProvider()
    {
        return [
            [new EntityConfigId('test', 'Test\Entity'), null],
            [new FieldConfigId('test', 'Test\Entity', 'testField', 'integer'), 'testField'],
        ];
    }

    /**
     * @dataProvider getFieldTypeProvider
     */
    public function testGetFieldType($configId, $expected)
    {
        $this->assertSame(
            $expected,
            $this->typeHelper->getFieldType($configId)
        );
    }

    public function getFieldTypeProvider()
    {
        return [
            [new EntityConfigId('test', 'Test\Entity'), null],
            [new FieldConfigId('test', 'Test\Entity', 'testField', 'integer'), 'integer'],
        ];
    }

    public function testGetImmutableNoConfig()
    {
        $scope     = 'test_scope';
        $className = 'Test\Entity';

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, null)
            ->will($this->returnValue(false));
        $configProvider->expects($this->never())
            ->method('getConfig');

        $this->assertNull(
            $this->typeHelper->getImmutable($scope, $className)
        );
    }

    /**
     * @dataProvider getImmutableProvider
     */
    public function testGetImmutable($value, $fieldName = null)
    {
        $scope     = 'test_scope';
        $className = 'Test\Entity';

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('immutable')
            ->will($this->returnValue($value));

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(true));
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($config));

        $this->assertSame(
            $value,
            $this->typeHelper->getImmutable($scope, $className, $fieldName)
        );
    }

    public function getImmutableProvider()
    {
        return [
            [true, null],
            [false, null],
            [null, null],
            [['val1', 'val2'], null],
            [true, 'testField'],
            [false, 'testField'],
            [null, 'testField'],
            [['val1', 'val2'], 'testField'],
        ];
    }

    /**
     * @dataProvider isImmutableProvider
     */
    public function testIsImmutable($value, $expected, $fieldName = null)
    {
        $scope     = 'test_scope';
        $className = 'Test\Entity';

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('immutable')
            ->will($this->returnValue($value));

        $configProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->will($this->returnValue($configProvider));
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(true));
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($config));

        $this->assertSame(
            $expected,
            $this->typeHelper->isImmutable($scope, $className, $fieldName)
        );
    }

    public function isImmutableProvider()
    {
        return [
            [true, true, null],
            [false, false, null],
            [null, false, null],
            [['val1', 'val2'], false, null],
            [true, true, 'testField'],
            [false, false, 'testField'],
            [null, false, 'testField'],
            [['val1', 'val2'], false, 'testField'],
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
