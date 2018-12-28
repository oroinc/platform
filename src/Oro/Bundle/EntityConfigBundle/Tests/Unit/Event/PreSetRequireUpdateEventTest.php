<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Event\PreSetRequireUpdateEvent;

class PreSetRequireUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    public function testIsUpdateRequired()
    {
        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);

        $event = new PreSetRequireUpdateEvent([], $configManager);
        self::assertTrue($event->isUpdateRequired());

        $event->setUpdateRequired(false);
        self::assertFalse($event->isUpdateRequired());
    }
}
