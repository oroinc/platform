<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigSettingsUpdateEventTest extends TestCase
{
    private const SETTINGS = ['a' => 'b'];

    private ConfigManager&MockObject $configManager;
    private ConfigSettingsUpdateEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->event = new ConfigSettingsUpdateEvent($this->configManager, self::SETTINGS);
    }

    public function testGetConfigManager(): void
    {
        $this->assertSame($this->configManager, $this->event->getConfigManager());
    }

    public function testSettings(): void
    {
        $this->assertEquals(self::SETTINGS, $this->event->getSettings());
        $newSettings = ['c' => true];
        $this->event->setSettings($newSettings);
        $this->assertEquals($newSettings, $this->event->getSettings());
    }
}
