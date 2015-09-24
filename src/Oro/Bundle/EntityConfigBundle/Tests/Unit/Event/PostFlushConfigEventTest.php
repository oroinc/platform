<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;

class PostFlushConfigEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $models = [
            new EntityConfigModel()
        ];
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new PostFlushConfigEvent($models, $configManager);
        $this->assertEquals($models, $event->getModels());
        $this->assertSame($configManager, $event->getConfigManager());
    }
}
