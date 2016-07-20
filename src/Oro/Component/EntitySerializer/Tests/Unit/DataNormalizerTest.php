<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\ConfigConverter;
use Oro\Component\EntitySerializer\DataNormalizer;

class DataNormalizerTest extends \PHPUnit_Framework_TestCase
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
            'single_property_path'                                       => [
                'config'       => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'isPrimary' => ['property_path' => 'primary'],
                                'primary'   => null
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'     => 123,
                        'phones' => [
                            ['number' => '123-456', 'primary' => true],
                            ['number' => '456-789', 'primary' => false],
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'     => 123,
                        'phones' => [
                            ['number' => '123-456', 'isPrimary' => true],
                            ['number' => '456-789', 'isPrimary' => false],
                        ]
                    ]
                ]
            ],
            'metadata_property_path'                                     => [
                'config'       => [
                    'fields' => [
                        'entity'    => ['property_path' => '__class__'],
                        '__class__' => null
                    ]
                ],
                'data'         => [
                    [
                        'id'        => 123,
                        '__class__' => 'Test\Class'
                    ]
                ],
                'expectedData' => [
                    [
                        'id'     => 123,
                        'entity' => 'Test\Class'
                    ]
                ]
            ],
            'field_name_equals_to_metadata_property_path'                => [
                'config'       => [
                    'fields' => [
                        '__class__' => ['property_path' => '__class__'],
                    ]
                ],
                'data'         => [
                    [
                        'id'        => 123,
                        '__class__' => 'Test\Class'
                    ]
                ],
                'expectedData' => [
                    [
                        'id'        => 123,
                        '__class__' => 'Test\Class'
                    ]
                ]
            ],
            'metadata_field_name_with_property_path'                     => [
                'config'       => [
                    'fields' => [
                        '__class__' => null,
                    ]
                ],
                'data'         => [
                    [
                        'id'        => 123,
                        '__class__' => 'Test\Class'
                    ]
                ],
                'expectedData' => [
                    [
                        'id'        => 123,
                        '__class__' => 'Test\Class'
                    ]
                ]
            ],
            'property_path'                                              => [
                'config'       => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name'],
                        'contact'     => [
                            'fields' => [
                                'id'   => null,
                                'name' => null
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'      => 123,
                        'contact' => [
                            'id'   => 456,
                            'name' => 'contact_name'
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'          => 123,
                        'contactName' => 'contact_name',
                        'contact'     => [
                            'id' => 456
                        ]
                    ]
                ]
            ],
            'property_path_with_null_child'                              => [
                'config'       => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name'],
                        'contact'     => [
                            'fields' => [
                                'id'   => null,
                                'name' => null
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'      => 123,
                        'contact' => null
                    ]
                ],
                'expectedData' => [
                    [
                        'id'          => 123,
                        'contactName' => null,
                        'contact'     => null
                    ]
                ]
            ],
            'property_path_with_null_child_id'                           => [
                'config'       => [
                    'fields' => [
                        'id'      => null,
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'fields'           => ['id' => null],
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'      => 123,
                        'contact' => null
                    ]
                ],
                'expectedData' => [
                    [
                        'id'      => 123,
                        'contact' => null
                    ]
                ]
            ],
            'property_path_without_child'                                => [
                'config'       => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name'],
                        'contact'     => [
                            'fields' => [
                                'id'   => null,
                                'name' => null
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id' => 123
                    ]
                ],
                'expectedData' => [
                    [
                        'id'          => 123,
                        'contactName' => null
                    ]
                ]
            ],
            'property_path_with_id_only_child'                           => [
                'config'       => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name'],
                        'contact'     => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'contact.id',
                            'fields'           => [
                                'id'   => null,
                                'name' => null
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'      => 123,
                        'contact' => [
                            'id'   => 456,
                            'name' => 'contact_name'
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'          => 123,
                        'contactName' => 'contact_name',
                        'contact'     => 456
                    ]
                ]
            ],
            'property_path_with_exclusion'                               => [
                'config'       => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name'],
                        'contact'     => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'name' => null
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'      => 123,
                        'contact' => [
                            'name' => 'contact_name'
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'          => 123,
                        'contactName' => 'contact_name'
                    ]
                ]
            ],
            'deep_property_path'                                         => [
                'config'       => [
                    'fields' => [
                        'newField'    => ['property_path' => 'field'],
                        'field'       => null,
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'fields' => [
                                'id'       => null,
                                'newField' => ['property_path' => 'field'],
                                'field'    => null,
                                'account'  => [
                                    'fields' => [
                                        'id'       => null,
                                        'newField' => ['property_path' => 'field'],
                                        'field'    => null,
                                        'name'     => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'      => 123,
                        'field'   => 'field_value1',
                        'contact' => [
                            'id'      => 456,
                            'field'   => 'field_value2',
                            'account' => [
                                'id'    => 789,
                                'field' => 'field_value3',
                                'name'  => 'account_name',
                            ]
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'          => 123,
                        'newField'    => 'field_value1',
                        'accountName' => 'account_name',
                        'contact'     => [
                            'id'       => 456,
                            'newField' => 'field_value2',
                            'account'  => [
                                'id'       => 789,
                                'newField' => 'field_value3'
                            ]
                        ]
                    ]
                ]
            ],
            'deep_property_path_with_null_last_relation'                 => [
                'config'       => [
                    'fields' => [
                        'newField'    => ['property_path' => 'field'],
                        'field'       => null,
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'fields' => [
                                'id'       => null,
                                'newField' => ['property_path' => 'field'],
                                'field'    => null,
                                'account'  => [
                                    'fields' => [
                                        'id'       => null,
                                        'newField' => ['property_path' => 'field'],
                                        'field'    => null,
                                        'name'     => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'      => 123,
                        'field'   => 'field_value1',
                        'contact' => [
                            'id'      => 456,
                            'field'   => 'field_value2',
                            'account' => null
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'          => 123,
                        'newField'    => 'field_value1',
                        'accountName' => null,
                        'contact'     => [
                            'id'       => 456,
                            'newField' => 'field_value2',
                            'account'  => null
                        ]
                    ]
                ]
            ],
            'deep_property_path_with_null_immediate_relation'            => [
                'config'       => [
                    'fields' => [
                        'newField'    => ['property_path' => 'field'],
                        'field'       => null,
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'fields' => [
                                'id'       => null,
                                'newField' => ['property_path' => 'field'],
                                'field'    => null,
                                'account'  => [
                                    'fields' => [
                                        'id'       => null,
                                        'newField' => ['property_path' => 'field'],
                                        'field'    => null,
                                        'name'     => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'      => 123,
                        'field'   => 'field_value1',
                        'contact' => null
                    ]
                ],
                'expectedData' => [
                    [
                        'id'          => 123,
                        'newField'    => 'field_value1',
                        'accountName' => null,
                        'contact'     => null
                    ]
                ]
            ],
            'deep_property_path_with_id_only_child'                      => [
                'config'       => [
                    'fields' => [
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'contact.id',
                            'fields'           => [
                                'id'      => null,
                                'account' => [
                                    'fields' => [
                                        'name' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'      => 123,
                        'contact' => [
                            'id'      => 456,
                            'account' => [
                                'name' => 'account_name'
                            ]
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'          => 123,
                        'accountName' => 'account_name',
                        'contact'     => 456
                    ]
                ]
            ],
            'deep_property_path_with_exclusion'                          => [
                'config'       => [
                    'fields' => [
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'account' => [
                                    'exclusion_policy' => 'all',
                                    'fields'           => [
                                        'name' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'      => 123,
                        'contact' => [
                            'account' => [
                                'name' => 'account_name'
                            ]
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'          => 123,
                        'accountName' => 'account_name'
                    ]
                ]
            ],
            'deep_property_path_with_relation'                           => [
                'config'       => [
                    'fields' => [
                        'id'      => null,
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'id'      => null,
                                'name'    => null,
                                'account' => [
                                    'exclusion_policy' => 'all',
                                    'fields'           => ['id' => null],
                                    'property_path'    => 'account.id',
                                    'collapse'         => true
                                ]
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'      => 123,
                        'contact' => [
                            'id'      => 456,
                            'name'    => 'contact_name',
                            'account' => [
                                'id' => 789
                            ],
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'      => 123,
                        'contact' => [
                            'id'      => 456,
                            'name'    => 'contact_name',
                            'account' => 789
                        ]
                    ]
                ]
            ],
            'deep_property_path_with_excluded_relation'                  => [
                'config'       => [
                    'fields' => [
                        'id'      => null,
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'id'      => null,
                                'name'    => null,
                                'account' => [
                                    'exclusion_policy' => 'all',
                                    'fields'           => ['id' => null],
                                    'property_path'    => 'id',
                                    'exclude'          => true,
                                    'collapse'         => true
                                ]
                            ]
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'      => 123,
                        'contact' => [
                            'id'   => 456,
                            'name' => 'contact_name'
                        ]
                    ]
                ],
                'expectedData' => [
                    [
                        'id'      => 123,
                        'contact' => [
                            'id'   => 456,
                            'name' => 'contact_name'
                        ]
                    ]
                ]
            ],
            'invalid config (relation in config, but scalar in data)'    => [
                'config'       => [
                    'fields' => [
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'fields'           => ['id' => null],
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id'      => 123,
                        'contact' => 'test contact'
                    ]
                ],
                'expectedData' => [
                    [
                        'id'      => 123,
                        'contact' => 'test contact'
                    ]
                ]
            ],
            'invalid config (a config for not existing relation)'        => [
                'config'       => [
                    'fields' => [
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'fields'           => ['id' => null],
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id' => 123,
                    ]
                ],
                'expectedData' => [
                    [
                        'id' => 123,
                    ]
                ]
            ],
            'a config for not existing relation, but with property path' => [
                'config'       => [
                    'fields' => [
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'fields'           => ['id' => null],
                            'property_path'    => 'id'
                        ]
                    ]
                ],
                'data'         => [
                    [
                        'id' => 123,
                    ]
                ],
                'expectedData' => [
                    [
                        'contact' => 123,
                    ]
                ]
            ],
        ];
    }
}
