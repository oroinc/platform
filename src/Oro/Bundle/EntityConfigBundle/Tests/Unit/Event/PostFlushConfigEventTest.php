<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;

class PostFlushConfigEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $models = [
            new EntityConfigModel()
        ];
        $configManager = $this->createMock(ConfigManager::class);

        $event = new PostFlushConfigEvent($models, $configManager);
        $this->assertEquals($models, $event->getModels());
        $this->assertSame($configManager, $event->getConfigManager());
    }
}
