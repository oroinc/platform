<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\ConfigConverter;

class ConfigConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider convertConfigProvider
     */
    public function testConvertConfig($config, $expectedConfig)
    {
        $configConverter = new ConfigConverter();

        $this->assertEquals(
            $expectedConfig,
            $configConverter->convertConfig($config)->toArray()
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function convertConfigProvider()
    {
        return [
            'with all fields'                => [
                'config'         => [
                    'exclusion_policy'          => 'all',
                    'disable_partial_load'      => true,
                    'hints'                     => [
                        'hint1',
                        ['name' => 'hint2'],
                        ['name' => 'hint3', 'value' => 'val']
                    ],
                    'order_by'                  => ['field1' => 'DESC'],
                    'max_results'               => 123,
                    'has_more'                  => true,
                    'post_serialize'            => [get_class($this), 'postSerialize1'],
                    'post_serialize_collection' => [get_class($this), 'postSerializeCollection1'],
                    'fields'                    => [
                        'field1' => [
                            'property_path'             => 'field1_path',
                            'exclude'                   => true,
                            'collapse'                  => true,
                            'data_transformer'          => [
                                'service_id',
                                [get_class($this), 'dataTransformer1']
                            ],
                            'exclusion_policy'          => 'all',
                            'disable_partial_load'      => true,
                            'hints'                     => [
                                'hint10',
                                ['name' => 'hint11'],
                                ['name' => 'hint12', 'value' => 'val']
                            ],
                            'order_by'                  => ['field2' => 'DESC'],
                            'max_results'               => 456,
                            'has_more'                  => true,
                            'post_serialize'            => [get_class($this), 'postSerialize2'],
                            'post_serialize_collection' => [get_class($this), 'postSerializeCollection2'],
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy'          => 'all',
                    'disable_partial_load'      => true,
                    'hints'                     => [
                        'hint1',
                        'hint2',
                        ['name' => 'hint3', 'value' => 'val']
                    ],
                    'order_by'                  => ['field1' => 'DESC'],
                    'max_results'               => 123,
                    'has_more'                  => true,
                    'post_serialize'            => [get_class($this), 'postSerialize1'],
                    'post_serialize_collection' => [get_class($this), 'postSerializeCollection1'],
                    'fields'                    => [
                        'field1' => [
                            'property_path'             => 'field1_path',
                            'exclude'                   => true,
                            'collapse'                  => true,
                            'data_transformer'          => [
                                'service_id',
                                [get_class($this), 'dataTransformer1']
                            ],
                            'exclusion_policy'          => 'all',
                            'disable_partial_load'      => true,
                            'hints'                     => [
                                'hint10',
                                'hint11',
                                ['name' => 'hint12', 'value' => 'val']
                            ],
                            'order_by'                  => ['field2' => 'DESC'],
                            'max_results'               => 456,
                            'has_more'                  => true,
                            'post_serialize'            => [get_class($this), 'postSerialize2'],
                            'post_serialize_collection' => [get_class($this), 'postSerializeCollection2'],
                        ]
                    ]
                ],
            ],
            'exclusion_policy=none'          => [
                'config'         => [
                    'exclusion_policy' => 'none',
                    'fields'           => [
                        'field1' => [
                            'exclusion_policy' => 'none',
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'field1' => []
                    ]
                ],
            ],
            'disable_partial_load=false'     => [
                'config'         => [
                    'disable_partial_load' => false,
                    'fields'               => [
                        'field1' => [
                            'disable_partial_load' => false,
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'field1' => []
                    ]
                ],
            ],
            'empty order_by'                 => [
                'config'         => [
                    'order_by' => [],
                    'fields'   => [
                        'field1' => [
                            'order_by' => [],
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'field1' => []
                    ]
                ],
            ],
            'max_results=null'               => [
                'config'         => [
                    'max_results' => null,
                    'fields'      => [
                        'field1' => [
                            'max_results' => null,
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'field1' => []
                    ]
                ],
            ],
            'has_more=false'                 => [
                'config'         => [
                    'has_more' => false,
                    'fields'   => [
                        'field1' => [
                            'has_more' => false
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'field1' => []
                    ]
                ]
            ],
            'post_serialize=null'            => [
                'config'         => [
                    'post_serialize' => null,
                    'fields'         => [
                        'field1' => [
                            'post_serialize' => null,
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'field1' => []
                    ]
                ],
            ],
            'post_serialize_collection=null' => [
                'config'         => [
                    'post_serialize_collection' => null,
                    'fields'                    => [
                        'field1' => [
                            'post_serialize_collection' => null,
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'field1' => []
                    ]
                ],
            ],
            'empty property_path'            => [
                'config'         => [
                    'fields' => [
                        'field1' => [
                            'property_path' => '',
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'field1' => []
                    ]
                ],
            ],
            'exclude=false'                  => [
                'config'         => [
                    'fields' => [
                        'field1' => [
                            'exclude' => false,
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'field1' => []
                    ]
                ],
            ],
            'collapse=false'                 => [
                'config'         => [
                    'fields' => [
                        'field1' => [
                            'collapse' => false,
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'field1' => []
                    ]
                ],
            ],
            'data_transformer=null'          => [
                'config'         => [
                    'fields' => [
                        'field1' => [
                            'data_transformer' => null,
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'field1' => []
                    ]
                ],
            ],
        ];
    }

    public static function postSerialize1(array $item)
    {
        return $item;
    }

    public static function postSerialize2(array $item)
    {
        return $item;
    }

    public static function postSerializeCollection1(array $items)
    {
        return $items;
    }

    public static function postSerializeCollection2(array $items)
    {
        return $items;
    }

    public static function dataTransformer1($class, $property, $value, $config)
    {
    }
}
