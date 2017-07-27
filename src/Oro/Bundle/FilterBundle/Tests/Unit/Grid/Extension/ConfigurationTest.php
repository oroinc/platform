<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Grid\Extension;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\FilterBundle\Grid\Extension\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Processor
     */
    private $processor;

    public function setUp()
    {
        $this->configuration = new Configuration(['string', 'number']);
        $this->processor = new Processor();
    }

    /**
     * @dataProvider processConfigurationDataProvider
     * @param array $configs
     * @param array $expected
     */
    public function testProcessConfiguration(array $configs, array $expected)
    {
        $this->assertEquals($expected, $this->processor->processConfiguration($this->configuration, $configs));
    }

    /**
     * @dataProvider processInvalidConfigurationStructure
     * @param array $configs
     */
    public function testInvalidConfigurationStructure(array $configs)
    {
        $this->expectException(InvalidTypeException::class);
        $this->processor->processConfiguration($this->configuration, $configs);
    }

    /**
     * @dataProvider processInvalidConfigurationValues
     * @param array $configs
     */
    public function testInvalidConfigurationValues(array $configs)
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->processor->processConfiguration($this->configuration, $configs);
    }

    /**
     * @return array
     */
    public function processConfigurationDataProvider()
    {
        return [
            'empty' => [
                'configs' => [[]],
                'expected' => [
                    'columns' => [],
                    'default' => [],
                ]
            ],
            'valid' => [
                'configs' => [
                    'filters' => [
                        'columns' => [
                            'sku' => [
                                'type' => 'string',
                                'data_name' => 'test',
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'columns' => [
                        'sku' => [
                            'type' => 'string',
                            'data_name' => 'test',
                            'enabled' => true,
                            'visible' => true,
                            'translatable' => true,
                            'force_like' => false,
                            'min_length' => 0,
                            'max_length' => PHP_INT_MAX,
                        ],
                    ],
                    'default' => [],
                ],
            ],
            'valid force_like' => [
                'configs' => [
                    'filters' => [
                        'columns' => [
                            'sku' => [
                                'type' => 'string',
                                'data_name' => 'test',
                                'force_like' => true,
                                'min_length' => 3,
                                'max_length' => 99,
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'columns' => [
                        'sku' => [
                            'type' => 'string',
                            'data_name' => 'test',
                            'enabled' => true,
                            'visible' => true,
                            'translatable' => true,
                            'force_like' => true,
                            'min_length' => 3,
                            'max_length' => 99,
                        ],
                    ],
                    'default' => [],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function processInvalidConfigurationStructure()
    {
        return [
            ['filters' => ['asd' => 'asdaaa']],
        ];
    }

    /**
     * @return array
     */
    public function processInvalidConfigurationValues()
    {
        return [
            'invalid filter type' => [[
                'filters' => [
                    'columns' => [
                        'sku' => [
                            'type' => 'asd',
                        ],
                    ],
                ],
            ]],
            'lack of required nodes' => [[
                'filters' => [
                    'columns' => [
                        'sku' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ]],
            'invalid force_like option' => [[
                'filters' => [
                    'columns' => [
                        'sku' => [
                            'type' => 'string',
                            'data_name' => 'test',
                            'force_like' => 'string'
                        ],
                    ],
                ],
            ]],
            'invalid min_length option' => [[
                'filters' => [
                    'columns' => [
                        'sku' => [
                            'type' => 'string',
                            'data_name' => 'test',
                            'min_length' => 'string'
                        ],
                    ],
                ],
            ]],
            'invalid min_length value' => [[
                'filters' => [
                    'columns' => [
                        'sku' => [
                            'type' => 'string',
                            'data_name' => 'test',
                            'min_length' => -1,
                        ],
                    ],
                ],
            ]],
            'invalid max_length option' => [[
                'filters' => [
                    'columns' => [
                        'sku' => [
                            'type' => 'string',
                            'data_name' => 'test',
                            'max_length' => 'string'
                        ],
                    ],
                ],
            ]],
            'invalid max_length value' => [[
                'filters' => [
                    'columns' => [
                        'sku' => [
                            'type' => 'string',
                            'data_name' => 'test',
                            'max_length' => 0,
                        ],
                    ],
                ],
            ]],
            'invalid `default` type' => [[
                'filters' => [
                    'default' => 123,
                ],
            ]],
        ];
    }
}
