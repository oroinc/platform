<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use PHPUnit\Framework\TestCase;

class PostFlushConfigEventTest extends TestCase
{
    public function testEvent(): void
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
