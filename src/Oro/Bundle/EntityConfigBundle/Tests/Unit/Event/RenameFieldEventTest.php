<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Event\RenameFieldEvent;

class RenameFieldEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new RenameFieldEvent('Test\Class', 'testField', 'newField', $configManager);

        $this->assertEquals('Test\Class', $event->getClassName());
        $this->assertEquals('testField', $event->getFieldName());
        $this->assertEquals('newField', $event->getNewFieldName());
        $this->assertSame($configManager, $event->getConfigManager());
    }
}
