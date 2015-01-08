<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Event\FlushConfigEvent;

class FlushConfigEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $models = [];
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new FlushConfigEvent($models, $configManager);
        $this->assertSame($models, $event->getModels());
        $this->assertSame($configManager, $event->getConfigManager());
    }
}
