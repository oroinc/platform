<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\UIBundle\EventListener\ConfigSettingsListener;

class ConfigSettingsListenerTest extends \PHPUnit_Framework_TestCase
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
     * @param array $testSettings
     * @param array $expectedSettings
     * @dataProvider onBeforeDataProvider
     */
    public function testOnBeforeMethod($testSettings = [], $expectedSettings = [])
    {
        $configManagerMock = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = new ConfigSettingsUpdateEvent($configManagerMock, $testSettings);

        $this->configSettingsListener->onBeforeSave($event);

        $this->assertEquals($expectedSettings, $event->getSettings());
    }

    /**
     * @return array
     */
    public function onBeforeDataProvider()
    {
        return [
            'Does not affect non-target config fields' => [
                [
                    'some_config_key' => [
                        'value' => 'some value',
                    ],
                    'another_config_key' => [
                        'value' => 'http://localhost///',
                    ],
                ],
                [
                    'some_config_key' => [
                        'value' => 'some value',
                    ],
                    'another_config_key' => [
                        'value' => 'http://localhost///',
                    ],
                ],
            ],
            'Affects oro_ui.application_url config field' =>[
                [
                    'oro_ui.application_url' => [
                        'value' => 'http://localhost///',
                    ],
                    'another_config_key' => [
                        'value' => '/another value/',
                    ],
                ],
                [
                    'oro_ui.application_url' => [
                        'value' => 'http://localhost',
                    ],
                    'another_config_key' => [
                        'value' => '/another value/',
                    ],
                ]
            ]
        ];
    }
}
