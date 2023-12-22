<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $configuration = new Configuration();

        $this->assertInstanceOf(TreeBuilder::class, $configuration->getConfigTreeBuilder());
    }

    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration(array $config, array $expected): void
    {
        $processor = new Processor();

        $this->assertEquals($expected, $processor->processConfiguration(new Configuration(), $config));
    }

    public function processConfigurationDataProvider(): array
    {
        return [
            'empty' => [
                'configs' => [[]],
                'expected' => [
                    'settings' => [
                        'resolved' => true,
                        'country' => [
                            'value' => null,
                            'scope' => 'app'
                        ],
                        'currency' => [
                            'value' => null,
                            'scope' => 'app'
                        ],
                        'timezone' => [
                            'value' => \date_default_timezone_get(),
                            'scope' => 'app'
                        ],
                        'format_address_by_address_country' => [
                            'value' => true,
                            'scope' => 'app'
                        ],
                        'qwerty' => [
                            'value' => [],
                            'scope' => 'app'
                        ],
                        'quarter_start' => [
                            'value' => [
                                'month' => '1',
                                'day' => '1'
                            ],
                            'scope' => 'app'
                        ],
                        'temperature_unit' => [
                            'value' => 'fahrenheit',
                            'scope' => 'app'
                        ],
                        'wind_speed_unit' => [
                            'value' => 'miles_per_hour',
                            'scope' => 'app'
                        ],
                        'enabled_localizations' => [
                            'value' => [],
                            'scope' => 'app'
                        ],
                        'default_localization' => [
                            'value' => null,
                            'scope' => 'app'
                        ],
                        'use_localization_names_in_email_template_editor' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                    ],
                    'formatting_code' => Translator::DEFAULT_LOCALE,
                    'language' => Translator::DEFAULT_LOCALE,
                ]
            ]
        ];
    }
}
