<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Event\PreSetRequireUpdateEvent;
use PHPUnit\Framework\TestCase;

class PreSetRequireUpdateEventTest extends TestCase
{
    public function testIsUpdateRequired(): void
    {
        $configManager = $this->createMock(ConfigManager::class);

        $event = new PreSetRequireUpdateEvent([], $configManager);
        self::assertTrue($event->isUpdateRequired());

        $event->setUpdateRequired(false);
        self::assertFalse($event->isUpdateRequired());
    }
}
