<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;
use PHPUnit\Framework\TestCase;

class FieldConfigEventTest extends TestCase
{
    public function testEvent(): void
    {
        $configManager = $this->createMock(ConfigManager::class);

        $event = new FieldConfigEvent('Test\Class', 'testField', $configManager);

        $this->assertEquals('Test\Class', $event->getClassName());
        $this->assertEquals('testField', $event->getFieldName());
        $this->assertSame($configManager, $event->getConfigManager());
    }
}
