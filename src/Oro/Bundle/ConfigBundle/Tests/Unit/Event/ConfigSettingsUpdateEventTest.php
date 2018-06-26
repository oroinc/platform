<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

class ConfigSettingsUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var ConfigSettingsUpdateEvent
     */
    protected $event;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->settings = ['a' => 'b'];

        $this->event = new ConfigSettingsUpdateEvent($this->configManager, $this->settings);
    }

    public function testSettings()
    {
        $this->assertEquals($this->settings, $this->event->getSettings());
        $newSettings = ['c' => true];
        $this->event->setSettings($newSettings);
        $this->assertEquals($newSettings, $this->event->getSettings());
    }

    public function testGetConfigManager()
    {
        $this->assertEquals($this->configManager, $this->event->getConfigManager());
    }
}
