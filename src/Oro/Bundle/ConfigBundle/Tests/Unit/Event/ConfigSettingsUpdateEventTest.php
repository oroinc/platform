<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

class ConfigSettingsUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    private const SETTINGS = ['a' => 'b'];

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigSettingsUpdateEvent */
    private $event;

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
