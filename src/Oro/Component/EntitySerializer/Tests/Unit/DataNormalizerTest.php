<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\ConfigConverter;
use Oro\Component\EntitySerializer\DataNormalizer;

class DataNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalizeData($config, $data, $expectedData)
    {
        $configConverter = new ConfigConverter();
        $normalizer = new DataNormalizer();

        $configObject = $configConverter->convertConfig($config);

        $this->assertEquals(
            $expectedData,
            $normalizer->normalizeData($data, $configObject)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function normalizeDataProvider()
    {
        return [
            'excluded fields should be removed'      => [
                'config'       => [
                    '_excluded_fields' => ['name'],
                    'fields'           => [
                        'name'    => ['exclude' => true],
                        'contact' => [
                            '_excluded_fields' => ['firstName'],
                            'fields'           => [
                                'id'        => null,
                                'firstName' => ['exclude' => true],
                                'lastName'  => null
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'      => 123,
                        'name'    => 'Name',
                        'contact' => [
                            'id'        => 456,
                            'firstName' => 'First Name',
                            'lastName'  => 'Last Name',
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'      => 123,
                        'contact' => [
                            'id'       => 456,
                            'lastName' => 'Last Name',
                        ]
                    ]
                ]
            ],
            'collapsed to-one association'           => [
                'config'       => [
                    'fields' => [
                        'id'       => null,
                        'category' => [
                            'collapse'        => true,
                            '_collapse_field' => 'id',
                            'fields'          => [
                                'id'    => null,
                                'title' => null
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'       => 1,
                        'category' => [
                            'id'    => 2,
                            'title' => 3
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'       => 1,
                        'category' => 2
                    ]
                ]
            ],
            'collapsed to-many association'          => [
                'config'       => [
                    'fields' => [
                        'id'         => null,
                        'categories' => [
                            'collapse'        => true,
                            '_collapse_field' => 'id',
                            'fields'          => [
                                'id'    => null,
                                'title' => null
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'         => 1,
                        'categories' => [
                            ['id' => 2, 'title' => 'category 1'],
                            ['id' => 3, 'title' => 'category 2']
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'         => 1,
                        'categories' => [2, 3]
                    ]
                ]
            ],
            'to-one association'                     => [
                'config'       => [
                    'fields' => [
                        'id'       => null,
                        'category' => [
                            'fields' => [
                                'id'    => null,
                                'title' => [
                                    '_excluded_fields' => ['excludedField'],
                                    'fields'           => [
                                        'id' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'       => 1,
                        'category' => [
                            'id'    => 2,
                            'title' => [
                                'id'              => 3,
                                'additionalField' => 'value',
                                'excludedField'   => 'value1'
                            ]
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'       => 1,
                        'category' => [
                            'id'    => 2,
                            'title' => [
                                'id'              => 3,
                                'additionalField' => 'value'
                            ]
                        ]
                    ]
                ]
            ],
            'to-many association'                    => [
                'config'       => [
                    'fields' => [
                        'id'       => null,
                        'category' => [
                            'fields' => [
                                'id'     => null,
                                'titles' => [
                                    '_excluded_fields' => ['excludedField'],
                                    'fields'           => [
                                        'id' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'       => 1,
                        'category' => [
                            'id'     => 2,
                            'titles' => [
                                ['id' => 3, 'additionalField' => 'value1', 'excludedField' => 'value1'],
                                ['id' => 4, 'additionalField' => 'value2', 'excludedField' => 'value2']
                            ]
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'       => 1,
                        'category' => [
                            'id'     => 2,
                            'titles' => [
                                ['id' => 3, 'additionalField' => 'value1'],
                                ['id' => 4, 'additionalField' => 'value2']
                            ]
                        ]
                    ]
                ]
            ],
            'association with info record'           => [
                'config'       => [
                    'fields' => [
                        'id'       => null,
                        'category' => [
                            'fields' => [
                                'id'     => null,
                                'titles' => [
                                    '_excluded_fields' => ['excludedField'],
                                    'fields'           => [
                                        'id' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'       => 1,
                        'category' => [
                            'id'     => 2,
                            'titles' => [
                                0   => ['id' => 3, 'additionalField' => 'value1', 'excludedField' => 'value1'],
                                1   => ['id' => 4, 'additionalField' => 'value2', 'excludedField' => 'value2'],
                                '_' => ['has_more' => true, 'excludedField' => 'value1']
                            ]
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'       => 1,
                        'category' => [
                            'id'     => 2,
                            'titles' => [
                                0   => ['id' => 3, 'additionalField' => 'value1'],
                                1   => ['id' => 4, 'additionalField' => 'value2'],
                                '_' => ['has_more' => true, 'excludedField' => 'value1']
                            ]
                        ]
                    ]
                ]
            ],
            'collapsed association with info record' => [
                'config'       => [
                    'fields' => [
                        'id'         => null,
                        'categories' => [
                            'collapse'        => true,
                            '_collapse_field' => 'id',
                            'fields'          => [
                                'id'    => null,
                                'title' => null
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'         => 1,
                        'categories' => [
                            0   => ['id' => 2, 'title' => 'category 1'],
                            1   => ['id' => 3, 'title' => 'category 2'],
                            '_' => ['has_more' => true, 'id' => 3]
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'         => 1,
                        'categories' => [
                            0   => 2,
                            1   => 3,
                            '_' => ['has_more' => true, 'id' => 3]
                        ]
                    ]
                ]
            ],
        ];
    }
}
