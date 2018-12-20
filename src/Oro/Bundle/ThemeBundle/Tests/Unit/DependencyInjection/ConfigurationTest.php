<?php


namespace Oro\Bundle\ThemeBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();

        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);
    }

    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration($configs, $expected)
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $this->assertEquals($expected, $processor->processConfiguration($configuration, $configs));
    }

    public function testInvalidConfiguration()
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
        $configuration = new Configuration();
        $processor = new Processor();
        $processor->processConfiguration($configuration, $configs);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processConfigurationDataProvider()
    {
        return [
            'empty' => [
                'configs' => [[]],
                'expected' => ['themes' => []]
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
                                'screenshot' => 'screenshot.png'
                            ]
                        ]
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
                        ]
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
                        ]
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
                        ]
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
                    ]
                ]
            ]
        ];
    }
}
