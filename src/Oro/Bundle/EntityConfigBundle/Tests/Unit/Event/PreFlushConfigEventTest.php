<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;

class PreFlushConfigEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $config1 = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $config2 = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $configs = ['scope1' => $config1, 'scope2' => $config2];

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new PreFlushConfigEvent($configs, $configManager);

        $this->assertSame($configManager, $event->getConfigManager());
        $this->assertEquals($configs, $event->getConfigs());
        $this->assertSame($config1, $event->getConfig('scope1'));
        $this->assertSame($config2, $event->getConfig('scope2'));
        $this->assertNull($event->getConfig('another_scope'));
    }

    public function testGetClass()
    {
        $config1 = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $config2 = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $configs = ['scope1' => $config1, 'scope2' => $config2];

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $className = 'Test\Entity';

        $event = new PreFlushConfigEvent($configs, $configManager);

        $config1->expects($this->once())
            ->method('getId')
            ->willReturn(new EntityConfigId('scope1', $className));
        $config2->expects($this->never())
            ->method('getId');

        $this->assertEquals($className, $event->getClassName());
        // test that a local cache is used
        $this->assertEquals($className, $event->getClassName());
    }

    /**
     * @dataProvider isFieldConfigDataProvider
     */
    public function testIsFieldConfig($configId, $expectedResult)
    {
        $config1 = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $config2 = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $configs = ['scope1' => $config1, 'scope2' => $config2];

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new PreFlushConfigEvent($configs, $configManager);

        $config1->expects($this->once())
            ->method('getId')
            ->willReturn($configId);
        $config2->expects($this->never())
            ->method('getId');

        $this->assertEquals($expectedResult, $event->isFieldConfig());
        // test that a local cache is used
        $this->assertEquals($expectedResult, $event->isFieldConfig());
    }

    public function isFieldConfigDataProvider()
    {
        return [
            [new EntityConfigId('scope1', 'Test\Entity'), false],
            [new FieldConfigId('scope1', 'Test\Entity', 'testField'), true]
        ];
    }

    /**
     * @dataProvider isEntityConfigDataProvider
     */
    public function testIsEntityConfig($configId, $expectedResult)
    {
        $config1 = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $config2 = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $configs = ['scope1' => $config1, 'scope2' => $config2];

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new PreFlushConfigEvent($configs, $configManager);

        $config1->expects($this->once())
            ->method('getId')
            ->willReturn($configId);
        $config2->expects($this->never())
            ->method('getId');

        $this->assertEquals($expectedResult, $event->isEntityConfig());
        // test that a local cache is used
        $this->assertEquals($expectedResult, $event->isEntityConfig());
    }

    public function isEntityConfigDataProvider()
    {
        return [
            [new EntityConfigId('scope1', 'Test\Entity'), true],
            [new FieldConfigId('scope1', 'Test\Entity', 'testField'), false]
        ];
    }
}
