<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\ConfigConverter;
use Oro\Component\EntitySerializer\ConfigNormalizer;

class ConfigNormalizerTest extends \PHPUnit\Framework\TestCase
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
            'order_by'                                                => [
                'config'         => [
                    'fields' => [
                        'phones' => [
                            'order_by' => ['primary' => 'DESC']
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
            'excluded_fields'                                         => [
                'config'         => [
                    'fields' => [
                        'id'     => ['exclude' => true],
                        'phones' => [
                            'fields' => [
                                'id'   => null,
                                'name' => ['exclude' => true]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    '_excluded_fields' => ['id'],
                    'fields'           => [
                        'id'     => ['exclude' => true],
                        'phones' => [
                            '_excluded_fields' => ['name'],
                            'fields'           => [
                                'id'   => null,
                                'name' => ['exclude' => true]
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    '_excluded_fields' => ['id'],
                    'fields'           => [
                        'id'     => ['exclude' => true],
                        'phones' => [
                            '_excluded_fields' => ['name'],
                            'fields'           => [
                                'id'   => [],
                                'name' => ['exclude' => true]
                            ]
                        ]
                    ]
                ],
            ],
            'collapsed_related_entity_single_field_config'            => [
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
                            'collapse'         => true,
                            '_collapse_field'  => 'id',
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
                            'collapse'         => true,
                            '_collapse_field'  => 'id',
                            'fields'           => [
                                'id' => []
                            ]
                        ]
                    ]
                ],
            ],
            'collapsed_related_entity'                                => [
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
                            'collapse'         => true,
                            '_collapse_field'  => 'id',
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
                            'collapse'         => true,
                            '_collapse_field'  => 'id',
                            'fields'           => [
                                'id' => []
                            ]
                        ]
                    ]
                ],
            ],
            'collapsed_related_entity_with_excluded_fields'           => [
                'config'         => [
                    'fields' => [
                        'id'      => null,
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'collapse'         => true,
                            'fields'           => [
                                'id'   => null,
                                'name' => ['exclude' => true]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'id'      => null,
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'collapse'         => true,
                            '_collapse_field'  => 'id',
                            '_excluded_fields' => ['name'],
                            'fields'           => [
                                'id'   => null,
                                'name' => ['exclude' => true]
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'id'      => [],
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'collapse'         => true,
                            '_collapse_field'  => 'id',
                            '_excluded_fields' => ['name'],
                            'fields'           => [
                                'id'   => [],
                                'name' => ['exclude' => true]
                            ]
                        ]
                    ]
                ],
            ],
            'collapsed_related_entity_with_property_path'             => [
                'config'         => [
                    'fields' => [
                        'id'         => null,
                        'newContact' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'contact',
                            'collapse'         => true,
                            'fields'           => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    '_renamed_fields' => ['contact' => 'newContact'],
                    'fields'          => [
                        'id'         => null,
                        'newContact' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'contact',
                            'collapse'         => true,
                            '_collapse_field'  => 'id',
                            'fields'           => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    '_renamed_fields' => ['contact' => 'newContact'],
                    'fields'          => [
                        'id'         => [],
                        'newContact' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'contact',
                            'collapse'         => true,
                            '_collapse_field'  => 'id',
                            'fields'           => [
                                'id' => []
                            ]
                        ]
                    ]
                ],
            ],
            'collapsed_related_entity_with_renamed_id'                => [
                'config'         => [
                    'fields' => [
                        'id'      => null,
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'collapse'         => true,
                            'fields'           => [
                                'name' => ['property_path' => 'id']
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'fields' => [
                        'id'      => null,
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'collapse'         => true,
                            '_collapse_field'  => 'name',
                            '_renamed_fields'  => ['id' => 'name'],
                            'fields'           => [
                                'name' => ['property_path' => 'id']
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    'fields' => [
                        'id'      => [],
                        'contact' => [
                            'exclusion_policy' => 'all',
                            'collapse'         => true,
                            '_collapse_field'  => 'name',
                            '_renamed_fields'  => ['id' => 'name'],
                            'fields'           => [
                                'name' => ['property_path' => 'id']
                            ]
                        ]
                    ]
                ],
            ],
            'field_property_path'                                     => [
                'config'         => [
                    'fields' => [
                        'name'   => ['property_path' => 'label'],
                        'phones' => [
                            'fields' => [
                                'isPrimary' => ['property_path' => 'primary']
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    '_renamed_fields' => ['label' => 'name'],
                    'fields'          => [
                        'name'   => ['property_path' => 'label'],
                        'phones' => [
                            '_renamed_fields' => ['primary' => 'isPrimary'],
                            'fields'          => [
                                'isPrimary' => ['property_path' => 'primary']
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    '_renamed_fields' => ['label' => 'name'],
                    'fields'          => [
                        'name'   => ['property_path' => 'label'],
                        'phones' => [
                            '_renamed_fields' => ['primary' => 'isPrimary'],
                            'fields'          => [
                                'isPrimary' => ['property_path' => 'primary']
                            ]
                        ]
                    ]
                ],
            ],
            'field_property_path_with_data_transformer'               => [
                'config'         => [
                    'fields' => [
                        'name'   => [
                            'property_path'    => 'label',
                            'data_transformer' => 'name_field_transformer'
                        ],
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
                    '_renamed_fields' => ['label' => 'name'],
                    'fields'          => [
                        'name'   => [
                            'property_path'    => 'label',
                            'data_transformer' => 'name_field_transformer'
                        ],
                        'phones' => [
                            '_renamed_fields' => ['primary' => 'isPrimary'],
                            'fields'          => [
                                'isPrimary' => [
                                    'property_path'    => 'primary',
                                    'data_transformer' => 'primary_field_transformer'
                                ],
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    '_renamed_fields' => ['label' => 'name'],
                    'fields'          => [
                        'name'   => [
                            'property_path'    => 'label',
                            'data_transformer' => ['name_field_transformer']
                        ],
                        'phones' => [
                            '_renamed_fields' => ['primary' => 'isPrimary'],
                            'fields'          => [
                                'isPrimary' => [
                                    'property_path'    => 'primary',
                                    'data_transformer' => ['primary_field_transformer']
                                ],
                            ]
                        ]
                    ]
                ],
            ],
            'metadata_property_path'                                  => [
                'config'         => [
                    'fields' => [
                        'entity' => ['property_path' => '__class__']
                    ]
                ],
                'expectedConfig' => [
                    '_renamed_fields' => ['__class__' => 'entity'],
                    'fields'          => [
                        'entity' => ['property_path' => '__class__']
                    ]
                ],
                'configObject'   => [
                    '_renamed_fields' => ['__class__' => 'entity'],
                    'fields'          => [
                        'entity' => ['property_path' => '__class__']
                    ]
                ],
            ],
            'dependency_on_not_configured_relation_field'             => [
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
            'dependency_on_configured_relation_field'                 => [
                'config'         => [
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
            'dependency_on_not_configured_relation'                   => [
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
            'dependency_on_relation_with_single_field_config'         => [
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
                            'collapse'         => true,
                            '_collapse_field'  => 'id',
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
                            'collapse'         => true,
                            '_collapse_field'  => 'id',
                            'fields'           => [
                                'id'   => [],
                                'name' => []
                            ]
                        ]
                    ]
                ],
            ],
            'dependency_on_excluded_relation'                         => [
                'config'         => [
                    'fields' => [
                        'phoneNumber' => ['property_path' => 'address.phone'],
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'address'     => ['exclude' => true],
                        'contact'     => [
                            'fields' => [
                                'account' => ['exclude' => true]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    '_excluded_fields' => ['address'],
                    'fields'           => [
                        'phoneNumber' => ['property_path' => 'address.phone'],
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'address'     => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'phone' => null
                            ]
                        ],
                        'contact'     => [
                            '_excluded_fields' => ['account'],
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
                'configObject'   => [
                    '_excluded_fields' => ['address'],
                    'fields'           => [
                        'phoneNumber' => ['property_path' => 'address.phone'],
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'address'     => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'phone' => []
                            ]
                        ],
                        'contact'     => [
                            '_excluded_fields' => ['account'],
                            'fields'           => [
                                'account' => [
                                    'exclusion_policy' => 'all',
                                    'fields'           => [
                                        'name' => []
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'dependency_on_excluded_relation (links after relations)' => [
                'config'         => [
                    'fields' => [
                        'address'     => ['exclude' => true],
                        'contact'     => [
                            'fields' => [
                                'account' => ['exclude' => true]
                            ]
                        ],
                        'phoneNumber' => ['property_path' => 'address.phone'],
                        'accountName' => ['property_path' => 'contact.account.name']
                    ]
                ],
                'expectedConfig' => [
                    '_excluded_fields' => ['address'],
                    'fields'           => [
                        'phoneNumber' => ['property_path' => 'address.phone'],
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'address'     => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'phone' => null
                            ]
                        ],
                        'contact'     => [
                            '_excluded_fields' => ['account'],
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
                'configObject'   => [
                    '_excluded_fields' => ['address'],
                    'fields'           => [
                        'phoneNumber' => ['property_path' => 'address.phone'],
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'address'     => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'phone' => []
                            ]
                        ],
                        'contact'     => [
                            '_excluded_fields' => ['account'],
                            'fields'           => [
                                'account' => [
                                    'exclusion_policy' => 'all',
                                    'fields'           => [
                                        'name' => []
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'dependency_on_fields'                                    => [
                'config'         => [
                    'fields' => [
                        'newField'    => ['property_path' => 'field'],
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'fields' => [
                                'id'          => null,
                                'field'       => null,
                                'accountName' => ['property_path' => 'account.name'],
                                'account'     => [
                                    'fields' => [
                                        'id'    => null,
                                        'name'  => null,
                                        'field' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    '_renamed_fields' => ['field' => 'newField'],
                    'fields'          => [
                        'newField'    => ['property_path' => 'field'],
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'fields' => [
                                'id'          => null,
                                'field'       => null,
                                'accountName' => ['property_path' => 'account.name'],
                                'account'     => [
                                    'fields' => [
                                        'id'    => null,
                                        'name'  => null,
                                        'field' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    '_renamed_fields' => ['field' => 'newField'],
                    'fields'          => [
                        'newField'    => ['property_path' => 'field'],
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'contact'     => [
                            'fields' => [
                                'id'          => [],
                                'field'       => [],
                                'accountName' => ['property_path' => 'account.name'],
                                'account'     => [
                                    'fields' => [
                                        'id'    => [],
                                        'name'  => [],
                                        'field' => []
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'dependency_on_renamed_fields'                            => [
                'config'         => [
                    'fields' => [
                        'newField'    => ['property_path' => 'field'],
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'newContact'  => [
                            'property_path' => 'contact',
                            'fields'        => [
                                'id'         => null,
                                'newField'   => ['property_path' => 'field'],
                                'newAccount' => [
                                    'property_path' => 'account',
                                    'fields'        => [
                                        'id'       => null,
                                        'newName'  => ['property_path' => 'name'],
                                        'newField' => ['property_path' => 'field']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    '_renamed_fields' => ['field' => 'newField', 'contact' => 'newContact'],
                    'fields'          => [
                        'newField'    => ['property_path' => 'field'],
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'newContact'  => [
                            'property_path'   => 'contact',
                            '_renamed_fields' => ['field' => 'newField', 'account' => 'newAccount'],
                            'fields'          => [
                                'id'         => null,
                                'newField'   => ['property_path' => 'field'],
                                'newAccount' => [
                                    'property_path'   => 'account',
                                    '_renamed_fields' => ['name' => 'newName', 'field' => 'newField'],
                                    'fields'          => [
                                        'id'       => null,
                                        'newName'  => ['property_path' => 'name'],
                                        'newField' => ['property_path' => 'field']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'configObject'   => [
                    '_renamed_fields' => ['field' => 'newField', 'contact' => 'newContact'],
                    'fields'          => [
                        'newField'    => ['property_path' => 'field'],
                        'accountName' => ['property_path' => 'contact.account.name'],
                        'newContact'  => [
                            'property_path'   => 'contact',
                            '_renamed_fields' => ['field' => 'newField', 'account' => 'newAccount'],
                            'fields'          => [
                                'id'         => [],
                                'newField'   => ['property_path' => 'field'],
                                'newAccount' => [
                                    'property_path'   => 'account',
                                    '_renamed_fields' => ['name' => 'newName', 'field' => 'newField'],
                                    'fields'          => [
                                        'id'       => [],
                                        'newName'  => ['property_path' => 'name'],
                                        'newField' => ['property_path' => 'field']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'dependency_on_field_of_collapsed_relation'               => [
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
                            'collapse'         => true,
                            '_collapse_field'  => 'id',
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
                            'collapse'         => true,
                            '_collapse_field'  => 'id',
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
