<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    private function processConfiguration(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $config);
    }

    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration(array $configs, array $expected): void
    {
        self::assertEquals($expected, $this->processConfiguration($configs));
    }

    public function testInvalidConfiguration(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $configs = [
            [
                'active_theme' => 'foo',
                'themes' => [
                    'foo-bar' => [
                        'label' => 'Foo Theme',
                        'logo' => 'logo.png',
                        'icon' => 'favicon.ico',
                        'screenshot' => 'screenshot.png'
                    ]
                ]
            ]
        ];
        $this->processConfiguration($configs);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processConfigurationDataProvider(): array
    {
        return [
            'empty' => [
                'configs' => [[]],
                'expected' => [
                    'themes' => [],
                    'settings' => [
                        'resolved' => true,
                        'theme_configuration' => [
                            'value' => null,
                            'scope' => 'app'
                        ]
                    ]
                ]
            ],
            'full' => [
                'configs' => [
                    [
                        'active_theme' => 'foo',
                        'themes' => [
                            'foo' => [
                                'label' => 'Foo Theme',
                                'logo' => 'logo.png',
                                'icon' => 'favicon.ico',
                                'screenshot' => 'screenshot.png',
                            ]
                        ],
                        'settings' => [
                            'theme_configuration' => ['value' => 1],
                        ],
                    ]
                ],
                'expected' => [
                    'active_theme' => 'foo',
                    'themes' => [
                        'foo' => [
                            'label' => 'Foo Theme',
                            'logo' => 'logo.png',
                            'icon' => 'favicon.ico',
                            'screenshot' => 'screenshot.png'
                        ]
                    ],
                    'settings' => [
                        'resolved' => true,
                        'theme_configuration' => [
                            'value' => 1,
                            'scope' => 'app'
                        ]
                    ]
                ]
            ],
            'merge' => [
                'configs' => [
                    [
                        'active_theme' => 'foo',
                        'themes' => [
                            'foo' => [
                                'label' => 'Foo Theme',
                                'logo' => 'logo.png',
                                'icon' => 'favicon.ico',
                                'screenshot' => 'screenshot.png'
                            ]
                        ],
                        'settings' => [
                            'theme_configuration' => ['value' => 1],
                        ],
                    ],
                    [
                        'active_theme' => 'bar',
                        'themes' => [
                            'bar' => [
                                'label' => 'Bar Theme',
                                'logo' => 'logo.png',
                                'icon' => 'favicon.ico',
                                'screenshot' => 'screenshot.png'
                            ]
                        ],
                        'settings' => [
                            'theme_configuration' => ['value' => 2],
                        ],
                    ],
                    [
                        'themes' => [
                            'bar' => [
                                'label' => 'Bar Extended Theme',
                                'logo' => 'logo-extended.png',
                                'icon' => 'favicon-extended.ico',
                                'screenshot' => 'screenshot-extended.png'
                            ],
                            'foo-bar_bar' => [
                                'label' => 'Bar Extended Theme',
                                'logo' => 'logo-extended.png',
                                'icon' => 'favicon-extended.ico',
                                'screenshot' => 'screenshot-extended.png'
                            ]
                        ],
                        'settings' => [
                            'theme_configuration' => ['value' => 3],
                        ],
                    ]
                ],
                'expected' => [
                    'active_theme' => 'bar',
                    'themes' => [
                        'foo' => [
                            'label' => 'Foo Theme',
                            'logo' => 'logo.png',
                            'icon' => 'favicon.ico',
                            'screenshot' => 'screenshot.png'
                        ],
                        'bar' => [
                            'label' => 'Bar Extended Theme',
                            'logo' => 'logo-extended.png',
                            'icon' => 'favicon-extended.ico',
                            'screenshot' => 'screenshot-extended.png'
                        ],
                        'foo-bar_bar' => [
                            'label' => 'Bar Extended Theme',
                            'logo' => 'logo-extended.png',
                            'icon' => 'favicon-extended.ico',
                            'screenshot' => 'screenshot-extended.png'
                        ]
                    ],
                    'settings' => [
                        'resolved' => true,
                        'theme_configuration' => [
                            'value' => 3,
                            'scope' => 'app'
                        ]
                    ]
                ]
            ]
        ];
    }
}
