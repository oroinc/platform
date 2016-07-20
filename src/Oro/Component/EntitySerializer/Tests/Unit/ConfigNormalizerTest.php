<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\ConfigConverter;
use Oro\Component\EntitySerializer\ConfigNormalizer;

class ConfigNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider normalizeConfigProvider
     */
    public function testNormalizeConfig($config, $expectedConfig, $configObject)
    {
        $configConverter = new ConfigConverter();
        $normalizer = new ConfigNormalizer();

        $normalizedConfig = $normalizer->normalizeConfig($config);

        $this->assertEquals(
            $expectedConfig,
            $normalizedConfig,
            'normalized config'
        );
        $this->assertEquals(
            $expectedConfig,
            $normalizer->normalizeConfig($normalizedConfig),
            'normalized config should not be changed'
        );
        $this->assertEquals(
            $configObject,
            $configConverter->convertConfig($expectedConfig)->toArray(),
            'config object'
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function normalizeConfigProvider()
    {
        return [
            // @deprecated since 1.9. Use 'exclude' attribute for a field instead of 'excluded_fields' for an entity
            'excluded_fields'                                                => [
                'config'         => [
                    'fields' => [
                        'phones' => [
                            'excluded_fields' => ['name', 'code'],
                            'fields'          => [
                                'id'   => null,
                                'name' => null
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'id'   => null,
                                'name' => ['exclude' => true],
                                'code' => ['exclude' => true]
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'id'   => [],
                                'name' => ['exclude' => true],
                                'code' => ['exclude' => true]
                            ]
                        ]
                    ]
                ],
            ],
            // @deprecated since 1.9. Use 'order_by' attribute instead of 'orderBy'
            'order_by'                                                       => [
                'config'         => [
                    'fields' => [
                        'phones' => [
                            'orderBy' => ['primary' => 'DESC']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'phones' => [
                            'order_by' => ['primary' => 'DESC']
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'phones' => [
                            'order_by' => ['primary' => 'DESC']
                        ]
                    ]
                ],
            ],
            // @deprecated since 1.9. Use `property_path` attribute instead of 'result_name'
            'result_name'                                                    => [
                'config'         => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'primary' => ['result_name' => 'isPrimary']
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'isPrimary' => ['property_path' => 'primary'],
                                'primary'   => null
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'isPrimary' => ['property_path' => 'primary'],
                                'primary'   => []
                            ]
                        ]
                    ]
                ],
            ],
            // @deprecated since 1.9. Use `property_path` attribute instead of 'result_name'
            'result_name_with_data_transformer'                              => [
                'config'         => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'primary' => [
                                    'result_name'      => 'isPrimary',
                                    'data_transformer' => 'primary_field_transformer'
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'isPrimary' => ['property_path' => 'primary'],
                                'primary'   => ['data_transformer' => 'primary_field_transformer']
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'isPrimary' => ['property_path' => 'primary'],
                                'primary'   => ['data_transformer' => ['primary_field_transformer']]
                            ]
                        ]
                    ]
                ],
            ],
            'collapsed_related_entity'                                       => [
                'config'         => [
                    'fields' => [
                        'id'      => null,
                        'contact' => [
                            'fields' => 'id'
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'id'      => null,
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'contact.id',
                            'collapse'         => true,
                            'fields'           => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'id'      => [],
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'contact.id',
                            'collapse'         => true,
                            'fields'           => [
                                'id' => []
                            ]
                        ]
                    ]
                ],
            ],
            'collapsed_related_entity_full_definition'                       => [
                'config'         => [
                    'fields' => [
                        'id'      => null,
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'id',
                            'collapse'         => true,
                            'fields'           => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'id'      => null,
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'id',
                            'collapse'         => true,
                            'fields'           => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'id'      => [],
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'id',
                            'collapse'         => true,
                            'fields'           => [
                                'id' => []
                            ]
                        ]
                    ]
                ],
            ],
            'collapsed_related_entity_full_definition_without_property_path' => [
                'config'         => [
                    'fields' => [
                        'id'      => null,
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'collapse'         => true,
                            'fields'           => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'id'      => null,
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'contact.id',
                            'collapse'         => true,
                            'fields'           => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'id'      => [],
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'contact.id',
                            'collapse'         => true,
                            'fields'           => [
                                'id' => []
                            ]
                        ]
                    ]
                ],
            ],
            'single_property_path'                                           => [
                'config'         => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'isPrimary' => ['property_path' => 'primary']
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'isPrimary' => ['property_path' => 'primary'],
                                'primary'   => null
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'isPrimary' => ['property_path' => 'primary'],
                                'primary'   => []
                            ]
                        ]
                    ]
                ],
            ],
            'property_path_with_data_transformer'                            => [
                'config'         => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'isPrimary' => [
                                    'property_path'    => 'primary',
                                    'data_transformer' => 'primary_field_transformer'
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'isPrimary' => ['property_path' => 'primary'],
                                'primary'   => ['data_transformer' => 'primary_field_transformer']
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'phones' => [
                            'fields' => [
                                'isPrimary' => ['property_path' => 'primary'],
                                'primary'   => ['data_transformer' => ['primary_field_transformer']]
                            ]
                        ]
                    ]
                ],
            ],
            'deep_property_path_with_data_transformer'                       => [
                'config'         => [
                    'fields' => [
                        'phone_number' => [
                            'property_path'    => 'phone.number',
                            'data_transformer' => 'phone_number_field_transformer'
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'phone_number' => [
                            'property_path' => 'phone.number'
                        ],
                        'phone'        => [
                            'fields' => [
                                'number' => ['data_transformer' => 'phone_number_field_transformer']
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'phone_number' => [
                            'property_path' => 'phone.number'
                        ],
                        'phone'        => [
                            'fields' => [
                                'number' => ['data_transformer' => ['phone_number_field_transformer']]
                            ]
                        ]
                    ]
                ],
            ],
            'metadata_property_path'                                         => [
                'config'         => [
                    'fields' => [
                        'entity' => ['property_path' => '__class__']
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'entity'    => ['property_path' => '__class__'],
                        '__class__' => null
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'entity'    => ['property_path' => '__class__'],
                        '__class__' => []
                    ]
                ],
            ],
            'property_path'                                                  => [
                'config'         => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name'],
                        'contact'     => [
                            'fields' => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
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
                'configObject'   => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name'],
                        'contact'     => [
                            'fields' => [
                                'id'   => [],
                                'name' => []
                            ]
                        ]
                    ]
                ],
            ],
            'property_path_with_all_fields_of_child'                         => [
                'config'         => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name']
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name'],
                        'contact'     => [
                            'fields' => [
                                'name' => null
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name'],
                        'contact'     => [
                            'fields' => [
                                'name' => []
                            ]
                        ]
                    ]
                ],
            ],
            'property_path_with_id_only_child'                               => [
                'config'         => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name'],
                        'contact'     => [
                            'fields' => 'id'
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name'],
                        'contact'     => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'contact.id',
                            'collapse'         => true,
                            'fields'           => [
                                'id'   => null,
                                'name' => null
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name'],
                        'contact'     => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'contact.id',
                            'collapse'         => true,
                            'fields'           => [
                                'id'   => [],
                                'name' => []
                            ]
                        ]
                    ]
                ],
            ],
            'property_path_with_exclusion'                                   => [
                'config'         => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name'],
                        'contact'     => ['exclude' => true]
                    ]
                ],
                'expectedConfig' => [
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
                'configObject'   => [
                    'fields' => [
                        'contactName' => ['property_path' => 'contact.name'],
                        'contact'     => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'name' => []
                            ]
                        ]
                    ]
                ],
            ],
            'deep_property_path'                                             => [
                'config'         => [
                    'fields' => [
                        'newField'    => ['property_path' => 'field'],
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'fields' => [
                                'id'       => null,
                                'newField' => ['property_path' => 'field'],
                                'account'  => [
                                    'fields' => [
                                        'id'       => null,
                                        'newField' => ['property_path' => 'field'],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
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
                'configObject'   => [
                    'fields' => [
                        'newField'    => ['property_path' => 'field'],
                        'field'       => [],
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'fields' => [
                                'id'       => [],
                                'newField' => ['property_path' => 'field'],
                                'field'    => [],
                                'account'  => [
                                    'fields' => [
                                        'id'       => [],
                                        'newField' => ['property_path' => 'field'],
                                        'field'    => [],
                                        'name'     => []
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'deep_property_path_with_all_fields_of_child'                    => [
                'config'         => [
                    'fields' => [
                        'accountName' => ['property_path' => 'contact.account.name'],
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'fields' => [
                                'account' => [
                                    'fields' => [
                                        'name' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'fields' => [
                                'account' => [
                                    'fields' => [
                                        'name' => []
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'deep_property_path_with_id_only_child'                          => [
                'config'         => [
                    'fields' => [
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'fields' => 'id'
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'contact.id',
                            'collapse'         => true,
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
                'configObject'   => [
                    'fields' => [
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'contact.id',
                            'collapse'         => true,
                            'fields'           => [
                                'id'      => [],
                                'account' => [
                                    'fields' => [
                                        'name' => []
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ];
    }
}
