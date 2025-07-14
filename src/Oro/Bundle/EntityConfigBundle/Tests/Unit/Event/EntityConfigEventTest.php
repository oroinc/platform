<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Event\EntityConfigEvent;
use PHPUnit\Framework\TestCase;

class EntityConfigEventTest extends TestCase
{
    public function testEvent(): void
    {
        $configManager = $this->createMock(ConfigManager::class);

        $event = new EntityConfigEvent('Test\Class', $configManager);

        $this->assertEquals('Test\Class', $event->getClassName());
        $this->assertSame($configManager, $event->getConfigManager());
    }
}
