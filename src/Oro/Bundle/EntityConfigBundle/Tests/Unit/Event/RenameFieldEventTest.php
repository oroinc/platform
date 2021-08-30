<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Event\RenameFieldEvent;

class RenameFieldEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $configManager = $this->createMock(ConfigManager::class);

        $event = new RenameFieldEvent('Test\Class', 'testField', 'newField', $configManager);

        $this->assertEquals('Test\Class', $event->getClassName());
        $this->assertEquals('testField', $event->getFieldName());
        $this->assertEquals('newField', $event->getNewFieldName());
        $this->assertSame($configManager, $event->getConfigManager());
    }
}
