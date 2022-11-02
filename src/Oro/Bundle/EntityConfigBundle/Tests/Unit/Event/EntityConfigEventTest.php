<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Event\EntityConfigEvent;

class EntityConfigEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $configManager = $this->createMock(ConfigManager::class);

        $event = new EntityConfigEvent('Test\Class', $configManager);

        $this->assertEquals('Test\Class', $event->getClassName());
        $this->assertSame($configManager, $event->getConfigManager());
    }
}
