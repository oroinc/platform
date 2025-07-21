<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\UIBundle\EventListener\ConfigSettingsListener;
use PHPUnit\Framework\TestCase;

class ConfigSettingsListenerTest extends TestCase
{
    private ConfigSettingsListener $configSettingsListener;

    #[\Override]
    protected function setUp(): void
    {
        $this->configSettingsListener = new ConfigSettingsListener();
    }

    /**
     * @dataProvider onBeforeDataProvider
     */
    public function testOnBeforeMethod(string $given, string $expected): void
    {
        $configManagerMock = $this->createMock(ConfigManager::class);

        $event = new ConfigSettingsUpdateEvent($configManagerMock, ['value' => $given]);
        $this->configSettingsListener->onBeforeSave($event);

        $this->assertEquals($expected, $event->getSettings()['value']);
    }

    public function onBeforeDataProvider(): array
    {
        return [
            ['http://localhost', 'http://localhost'],
            ['http://localhost/', 'http://localhost'],
            ['http://localhost//', 'http://localhost'],
        ];
    }
}
