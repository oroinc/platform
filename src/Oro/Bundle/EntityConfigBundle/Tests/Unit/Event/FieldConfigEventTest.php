<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;

class FieldConfigEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $configManager = $this->createMock(ConfigManager::class);

        $event = new FieldConfigEvent('Test\Class', 'testField', $configManager);

        $this->assertEquals('Test\Class', $event->getClassName());
        $this->assertEquals('testField', $event->getFieldName());
        $this->assertSame($configManager, $event->getConfigManager());
    }
}
