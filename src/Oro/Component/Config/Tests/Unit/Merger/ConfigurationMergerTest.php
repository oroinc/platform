<?php

namespace Oro\Component\Config\Tests\Unit\Merger;

use Oro\Component\Config\Merger\ConfigurationMerger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigurationMergerTest extends \PHPUnit\Framework\TestCase
{
    private const BUNDLE1 = 'Oro\Bundle\TestBundle1\TestBundle1';
    private const BUNDLE2 = 'Oro\Bundle\TestBundle1\TestBundle2';
    private const BUNDLE3 = 'Oro\Bundle\TestBundle1\TestBundle3';

    /** @var ConfigurationMerger */
    private $merger;

    protected function setUp(): void
    {
        $this->merger = new ConfigurationMerger([self::BUNDLE1, self::BUNDLE2, self::BUNDLE3]);
    }

    /**
     * @dataProvider mergeConfigurationDataProvider
     */
    public function testMergeConfiguration(array $rawConfig, array $expected)
    {
        $configs = $this->merger->mergeConfiguration($rawConfig);

        $this->assertIsArray($configs);
        $this->assertEquals($expected, $configs);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function mergeConfigurationDataProvider(): array
    {
        return [
            'merge configuration from bundles' => [
                'rawConfig' => [
                    self::BUNDLE1 => [
                        'test_config' => [
                            'label' => 'Test Config',
                            'params' => ['test_param_bundle1']
                        ]
                    ],
                    self::BUNDLE2 => [
                        'test_config' => [
                            'label' => 'Test Config Replaced',
                            'params' => ['test_param_bundle2' => 'data']
                        ]
                    ]
                ],
                'expected' => [
                    'test_config' => [
                        'label' => 'Test Config Replaced',
                        'params' => ['test_param_bundle1', 'test_param_bundle2' => 'data']
                    ]
                ]
            ],
            'merge configuration from bundles with replace' => [
                'rawConfig' => [
                    self::BUNDLE1 => [
                        'test_config_base' => [
                            'array_base' => [
                                'base_param' => ['base_value']
                            ]
                        ],
                        'test_config' => [
                            'label' => 'Test Config',
                            'params' => ['test_param_bundle1'],
                            'array' => [
                                'single_param' => 'value',
                                'sub_array' => [
                                    'param_name' => 'param_value'
                                ]
                            ]
                        ]
                    ],
                    self::BUNDLE2 => [
                        'test_config' => [
                            'extends' => 'test_config_base',
                            'replace' => ['params', 'array_base'],
                            'params' => ['test_param_bundle2' => 'data'],
                            'array' => [
                                'replace' => ['sub_array'],
                                'sub_array' => [
                                    'replaced_param_name' => 'replaced_param_value'
                                ]
                            ]
                        ]
                    ]
                ],
                'expected' => [
                    'test_config_base' => [
                        'array_base' => [
                            'base_param' => ['base_value']
                        ]
                    ],
                    'test_config' => [
                        'label' => 'Test Config',
                        'params' => ['test_param_bundle2' => 'data'],
                        'array' => [
                            'single_param' => 'value',
                            'sub_array' => [
                                'replaced_param_name' => 'replaced_param_value'
                            ]
                        ]
                    ]
                ]
            ],
            'config with unknown bundle' => [
                'rawConfig' => [
                    self::BUNDLE1 => [
                        'test_config' => ['configurations']
                    ],
                    'UnknownBundle' => [
                        'test_config' => 'test'
                    ]
                ],
                'expected' => [
                    'test_config' => ['configurations']
                ]
            ],
            'config inheritance' => [
                'rawConfig' => [
                    self::BUNDLE1 => [
                        'test_config' => [
                            'label' => 'test label',
                            'params' => [
                                'param1' => 'value1'
                            ]
                        ],
                        'test_config_extended' => [
                            'extends' => 'test_config',
                            'params' => [
                                'param2' => 'value2'
                            ]
                        ]
                    ]
                ],
                'expected' => [
                    'test_config' => [
                        'label' => 'test label',
                        'params' => [
                            'param1' => 'value1'
                        ]
                    ],
                    'test_config_extended' => [
                        'label' => 'test label',
                        'params' => [
                            'param1' => 'value1',
                            'param2' => 'value2'
                        ]
                    ]
                ]
            ],
            'all cases' => [
                [
                    self::BUNDLE1 => [
                        'test_config1' => [
                            'label' => 'Test Config1',
                            'replace' => ['test'],
                            'params' => ['test_param_bundle1']
                        ],
                        'test_config2' => [
                            'extends' => 'test_config1'
                        ],
                        'test_config4' => [
                            'label' => 'Test Config1',
                            'some_config' => [
                                'sub_config1' => 'data1',
                                'sub_config2' => 'data2',
                                'sub_config3' => 'data3',
                            ]
                        ],
                        'test_config5' => [
                            'extends' => 'test_config3',
                            'replace' => ['params'],
                            'params' => ['my'],
                            'array' => [
                                'replace' => 'param',
                                'param' => [
                                    'value3'
                                ],
                                'single_param' => 123
                            ]
                        ]
                    ],
                    self::BUNDLE2 => [
                        'test_config1' => [
                            'replace' => ['params'],
                        ],
                        'test_config4' => [
                            'label' => 'Test Config4',
                            'some_config' => [
                                'replace' => ['sub_config1', 'sub_config3'],
                                'sub_config3' => 'replaced data'
                            ]
                        ]
                    ],
                    self::BUNDLE3 => [
                        'test_config1' => [
                            'replace' => ['params'],
                            'params' => ['test_param_bundle3']
                        ],
                        'test_config2' => [
                            'label' => 'Test Config2 Bundle3',
                            'extends' => 'test_config1',
                        ],
                        'test_config3' => [
                            'extends' => 'test_config2',
                            'params' => ['test_param_bundle3_new'],
                            'array' => [
                                'param' => [
                                    'value1',
                                    'value2'
                                ]
                            ]
                        ]
                    ],
                    'UnknownBundle' => [
                        'test_config1' => [
                            'replace' => ['params'],
                            'params' => ['test_param_bundle3']
                        ]
                    ]
                ],
                [
                    'test_config1' => [
                        'label' => 'Test Config1',
                        'params' => ['test_param_bundle3']
                    ],
                    'test_config2' => [
                        'label' => 'Test Config2 Bundle3',
                        'params' => ['test_param_bundle3']
                    ],
                    'test_config3' => [
                        'label' => 'Test Config2 Bundle3',
                        'params' => ['test_param_bundle3', 'test_param_bundle3_new'],
                        'array' => [
                            'param' => ['value1', 'value2']
                        ]
                    ],
                    'test_config4' => [
                        'label' => 'Test Config4',
                        'some_config' => ['sub_config2' => 'data2', 'sub_config3' => 'replaced data']
                    ],
                    'test_config5' => [
                        'label' => 'Test Config2 Bundle3',
                        'params' => ['my'],
                        'array' => [
                            'param' => ['value3'],
                            'single_param' => 123
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider mergeConfigurationExceptionDataProvider
     */
    public function testMergeConfigurationException(array $rawConfig, string $expectedMessage)
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->merger->mergeConfiguration($rawConfig);
    }

    public function mergeConfigurationExceptionDataProvider(): array
    {
        return [
            [
                [
                    self::BUNDLE1 => [
                        'test_config1' => [
                            'label' => 'Test Config1',
                            'extends' => 'test_config3',
                        ]
                    ],
                    self::BUNDLE2 => [
                        'test_config2' => [
                            'label' => 'Test Config2',
                            'extends' => 'test_config1',
                        ]
                    ],
                    self::BUNDLE3 => [
                        'test_config3' => [
                            'label' => 'Test Config3',
                            'extends' => 'test_config2',
                        ]
                    ]
                ],
                'Found circular "extends" references test_config1 and test_config2 configurations.'
            ],
            [
                [
                    self::BUNDLE2 => [
                        'test_config2' => [
                            'label' => 'Test Config2',
                            'extends' => 'test_config1',
                        ]
                    ]
                ],
                'Could not found configuration of test_config1 for dependant configuration test_config2.'
            ]
        ];
    }
}
