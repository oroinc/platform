<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Event\EntityConfigEvent;

class EntityConfigEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new EntityConfigEvent('Test\Class', $configManager);

        $this->assertEquals('Test\Class', $event->getClassName());
        $this->assertSame($configManager, $event->getConfigManager());
    }
}
