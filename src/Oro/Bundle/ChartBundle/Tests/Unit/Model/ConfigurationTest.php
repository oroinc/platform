<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model;

use Oro\Bundle\ChartBundle\Model\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration(array $configs, array $expected)
    {
        $this->assertEquals($expected, (new Processor())->processConfiguration(new Configuration(), $configs));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processConfigurationDataProvider(): array
    {
        return [
            'empty' => [
                'configs'  => [
                    [
                        'foo_chart' => [
                            'label' => 'Foo',
                            'data_schema' => [
                                [
                                    'name' => 'label',
                                    'label' => 'Category (X axis)',
                                    'required' => true,
                                    'default_type' => 'decimal'
                                ],
                                [
                                    'name' => 'value',
                                    'label' => 'Value (Y axis)',
                                    'required' => true,
                                    'default_type' => 'string'
                                ],
                            ],
                            'settings_schema' => [
                                [
                                    'name' => 'connect_dots_with_line',
                                    'label' => 'Connect line with dots',
                                    'type' => 'boolean'
                                ],
                                [
                                    'name' => 'advanced_option',
                                    'label' => 'Advanced option',
                                    'type' => 'string',
                                    'options' => [
                                        'foo' => 'bar'
                                    ]
                                ],
                            ],
                            'data_transformer' => 'foo_data_transformer_service',
                            'template' => 'FooTemplate.html.twig'
                        ]
                    ],
                    [
                        'bar_chart' => [
                            'label' => 'Bar',
                            'template' => 'BarTemplate.html.twig'
                        ]
                    ],
                ],
                'expected' => [
                    'foo_chart' => [
                        'label' => 'Foo',
                        'data_schema' => [
                            [
                                'label' => 'Category (X axis)',
                                'name' => 'label',
                                'required' => true,
                                'type_filter' => [],
                                'default_type' => 'decimal'
                            ],
                            [
                                'label' => 'Value (Y axis)',
                                'name' => 'value',
                                'required' => true,
                                'type_filter' => [],
                                'default_type' => 'string'
                            ],
                        ],
                        'settings_schema' => [
                            [
                                'name' => 'connect_dots_with_line',
                                'label' => 'Connect line with dots',
                                'type' => 'boolean',
                                'options' => [],
                            ],
                            [
                                'name' => 'advanced_option',
                                'label' => 'Advanced option',
                                'type' => 'string',
                                'options' => [
                                    'foo' => 'bar'
                                ],
                            ],
                        ],
                        'data_transformer' => 'foo_data_transformer_service',
                        'template' => 'FooTemplate.html.twig',
                        'default_settings' => [],
                        'xaxis' => [
                            'mode' => 'normal',
                            'noTicks' => 5
                        ]
                    ],
                    'bar_chart' => [
                        'label' => 'Bar',
                        'data_schema' => [],
                        'settings_schema' => [],
                        'default_settings' => [],
                        'template' => 'BarTemplate.html.twig',
                        'xaxis' => [
                            'mode' => 'normal',
                            'noTicks' => 5
                        ]

                    ],
                ]
            ],
            'with type' => [
                'configs'  => [
                    [
                        'type_chart' => [
                            'label' => 'Type',
                            'data_schema' => [
                                [
                                    'name' => 'label',
                                    'label' => 'Category (X axis)',
                                    'required' => true,
                                    'default_type' => 'decimal',
                                    'type' => 'month'
                                ],
                                [
                                    'name' => 'value',
                                    'label' => 'Value (Y axis)',
                                    'required' => true,
                                    'default_type' => 'string',
                                    'type' => 'currency'
                                ],
                            ],
                            'settings_schema' => [
                                [
                                    'name' => 'connect_dots_with_line',
                                    'label' => 'Connect line with dots',
                                    'type' => 'boolean'
                                ],
                                [
                                    'name' => 'advanced_option',
                                    'label' => 'Advanced option',
                                    'type' => 'string',
                                    'options' => [
                                        'foo' => 'bar'
                                    ]
                                ],
                            ],
                            'data_transformer' => 'foo_data_transformer_service',
                            'template' => 'FooTemplate.html.twig'
                        ]
                    ]
                ],
                'expected' => [
                    'type_chart' => [
                        'label' => 'Type',
                        'data_schema' => [
                            [
                                'label' => 'Category (X axis)',
                                'name' => 'label',
                                'required' => true,
                                'type_filter' => [],
                                'default_type' => 'decimal',
                                'type' => 'month'
                            ],
                            [
                                'label' => 'Value (Y axis)',
                                'name' => 'value',
                                'required' => true,
                                'type_filter' => [],
                                'default_type' => 'string',
                                'type' => 'currency'
                            ],
                        ],
                        'settings_schema' => [
                            [
                                'name' => 'connect_dots_with_line',
                                'label' => 'Connect line with dots',
                                'type' => 'boolean',
                                'options' => [],
                            ],
                            [
                                'name' => 'advanced_option',
                                'label' => 'Advanced option',
                                'type' => 'string',
                                'options' => [
                                    'foo' => 'bar'
                                ],
                            ],
                        ],
                        'data_transformer' => 'foo_data_transformer_service',
                        'template' => 'FooTemplate.html.twig',
                        'default_settings' => [],
                        'xaxis' => [
                            'mode' => 'normal',
                            'noTicks' => 5
                        ]

                    ]
                ]
            ]
        ];
    }
}
