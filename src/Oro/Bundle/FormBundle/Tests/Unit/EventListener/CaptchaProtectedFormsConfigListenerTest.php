<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsFormOptionsEvent;
use Oro\Bundle\FormBundle\DependencyInjection\Configuration;
use Oro\Bundle\FormBundle\EventListener\CaptchaProtectedFormsConfigListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CaptchaProtectedFormsConfigListenerTest extends TestCase
{
    private ConfigManager&MockObject $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
    }

    /** @dataProvider setConfigDataProvider */
    public function testSetConfig(
        string $scope,
        string $formOptionKey,
        array $allOptions,
        array $expectedResult
    ): void {
        $this->configManager->expects(self::any())
            ->method('getScopeEntityName')
            ->willReturn($scope);

        $listener = new CaptchaProtectedFormsConfigListener();
        $event = new ConfigSettingsFormOptionsEvent($this->configManager, $allOptions);
        $listener->setConfig($event);

        self::assertEquals($expectedResult, $event->getFormOptions($formOptionKey));
    }

    public function setConfigDataProvider(): array
    {
        return [
            'event without needed form options in global scope' => [
                'scope' => GlobalScopeManager::SCOPE_NAME,
                'formOptionKey' => 'formOptions',
                'allOptions' => ['formOptions' => ['key' => 'value']],
                'expectedResult' => ['key' => 'value']
            ],
            'event with needed form options in unknown scope' => [
                'scope' => 'unknown',
                'formOptionKey' => Configuration::getConfigKey(Configuration::CAPTCHA_PROTECTED_FORMS),
                'allOptions' => [
                    Configuration::getConfigKey(Configuration::CAPTCHA_PROTECTED_FORMS) => [
                        'key' => 'value'
                    ]
                ],
                'expectedResult' => [
                    'key' => 'value',
                    'target_field_options' => [
                        'scope' => 'unknown'
                    ],
                ]
            ],
            'event with needed form options in global scope' => [
                'scope' => GlobalScopeManager::SCOPE_NAME,
                'formOptionKey' => Configuration::getConfigKey(Configuration::CAPTCHA_PROTECTED_FORMS),
                'allOptions' => [
                    Configuration::getConfigKey(Configuration::CAPTCHA_PROTECTED_FORMS) => [
                        'key' => 'value'
                    ]
                ],
                'expectedResult' => [
                    'key' => 'value',
                    'target_field_options' => [
                        'scope' => GlobalScopeManager::SCOPE_NAME
                    ],
                ]
            ]
        ];
    }
}
