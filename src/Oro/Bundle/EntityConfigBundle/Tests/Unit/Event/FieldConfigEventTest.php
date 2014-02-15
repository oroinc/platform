<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;

class FieldConfigEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new FieldConfigEvent('Test\Class', 'testField', $configManager);

        $this->assertEquals('Test\Class', $event->getClassName());
        $this->assertEquals('testField', $event->getFieldName());
        $this->assertSame($configManager, $event->getConfigManager());
    }
}
