<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\UIBundle\EventListener\ConfigSettingsListener;

class ConfigSettingsListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigSettingsListener
     */
    protected $configSettingsListener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configSettingsListener = new ConfigSettingsListener();
    }

    /**
     * @param string $given
     * @param string $expected
     * @dataProvider onBeforeDataProvider
     */
    public function testOnBeforeMethod($given, $expected)
    {
        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManagerMock */
        $configManagerMock = $this->createMock(ConfigManager::class);

        $event = new ConfigSettingsUpdateEvent($configManagerMock, ['value' => $given]);
        $this->configSettingsListener->onBeforeSave($event);

        $this->assertEquals($expected, $event->getSettings()['value']);
    }

    /**
     * @return array
     */
    public function onBeforeDataProvider()
    {
        return [
            ['http://localhost', 'http://localhost'],
            ['http://localhost/', 'http://localhost'],
            ['http://localhost//', 'http://localhost'],
        ];
    }
}
