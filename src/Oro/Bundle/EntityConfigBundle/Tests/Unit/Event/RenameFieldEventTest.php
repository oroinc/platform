<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Event\RenameFieldEvent;
use PHPUnit\Framework\TestCase;

class RenameFieldEventTest extends TestCase
{
    public function testEvent(): void
    {
        $configManager = $this->createMock(ConfigManager::class);

        $event = new RenameFieldEvent('Test\Class', 'testField', 'newField', $configManager);

        $this->assertEquals('Test\Class', $event->getClassName());
        $this->assertEquals('testField', $event->getFieldName());
        $this->assertEquals('newField', $event->getNewFieldName());
        $this->assertSame($configManager, $event->getConfigManager());
    }
}
