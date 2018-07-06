<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Normalizer;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Normalizer\ConfigNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ConfigNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider normalizeConfigProvider
     */
    public function testNormalizeConfig($config, $expectedConfig)
    {
        $normalizer = new ConfigNormalizer();

        $configExtensionRegistry = new ConfigExtensionRegistry();
        $configLoaderFactory = new ConfigLoaderFactory($configExtensionRegistry);
        $configLoader = $configLoaderFactory->getLoader(ConfigUtil::DEFINITION);

        /** @var EntityDefinitionConfig $normalizedConfig */
        $normalizedConfig = $configLoader->load($config);
        $normalizer->normalizeConfig($normalizedConfig);

        self::assertEquals($expectedConfig, $normalizedConfig->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function normalizeConfigProvider()
    {
        return [
            'ignored fields'                                             => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1'       => [
                            'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                        ],
                        'field2'       => [
                            'property_path' => 'realField2'
                        ],
                        'association1' => [
                            'fields' => [
                                'association11' => [
                                    'fields' => [
                                        'field111' => [
                                            'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                                        ],
                                        'field112' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    '_renamed_fields'  => ['realField2' => 'field2'],
                    'fields'           => [
                        'field2'       => [
                            'property_path' => 'realField2'
                        ],
                        'association1' => [
                            'fields' => [
                                'association11' => [
                                    'fields' => [
                                        'field112' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'field depends on another field'                             => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1' => null,
                        'field2' => [
                            'depends_on' => ['field1']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1' => null,
                        'field2' => [
                            'depends_on' => ['field1']
                        ]
                    ]
                ]
            ],
            'field depends on excluded field'                            => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1' => [
                            'exclude' => true
                        ],
                        'field2' => [
                            'depends_on' => ['field1']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    '_excluded_fields' => ['field1'],
                    'fields'           => [
                        'field1' => null,
                        'field2' => [
                            'depends_on' => ['field1']
                        ]
                    ]
                ]
            ],
            'excluded field depends on another excluded field'           => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1' => [
                            'exclude' => true
                        ],
                        'field2' => [
                            'exclude'    => true,
                            'depends_on' => ['field1']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    '_excluded_fields' => ['field1', 'field2'],
                    'fields'           => [
                        'field1' => [
                            'exclude' => true
                        ],
                        'field2' => [
                            'exclude'    => true,
                            'depends_on' => ['field1']
                        ]
                    ]
                ]
            ],
            'field depends on excluded computed field'                   => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1' => [
                            'exclude' => true
                        ],
                        'field2' => [
                            'exclude'    => true,
                            'depends_on' => ['field1']
                        ],
                        'field3' => [
                            'depends_on' => ['field2']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    '_excluded_fields' => ['field1', 'field2'],
                    'fields'           => [
                        'field1' => null,
                        'field2' => [
                            'depends_on' => ['field1']
                        ],
                        'field3' => [
                            'depends_on' => ['field2']
                        ]
                    ]
                ]
            ],
            'nested field depends on another field'                      => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field' => [
                            'fields' => [
                                'field1' => [
                                    'exclude' => true
                                ],
                                'field2' => [
                                    'depends_on' => ['field1']
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field' => [
                            '_excluded_fields' => ['field1'],
                            'fields'           => [
                                'field1' => null,
                                'field2' => [
                                    'depends_on' => ['field1']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'field depends on association child field'                   => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'fields' => [
                                'field11' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'fields' => [
                                'field11' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ]
            ],
            'field depends on association undefined child field'         => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'fields' => [
                                'field12' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'fields' => [
                                'field11' => null,
                                'field12' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ]
            ],
            'field depends on undefined association child field'         => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field2' => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'fields' => [
                                'field11' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ]
            ],
            'field depends on association excluded child field'          => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'fields' => [
                                'field11' => [
                                    'exclude' => true
                                ]
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            '_excluded_fields' => ['field11'],
                            'fields'           => [
                                'field11' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ]
            ],
            'field depends on excluded association child field'          => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'exclude' => true,
                            'fields'  => [
                                'field11' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    '_excluded_fields' => ['association1'],
                    'fields'           => [
                        'association1' => [
                            'fields' => [
                                'field11' => null
                            ]
                        ],
                        'field2'       => [
                            'depends_on' => ['association1.field11']
                        ]
                    ]
                ]
            ],
            'field depends on excluded association and its child fields' => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field2'       => [
                            'depends_on' => ['association1.association11.field111']
                        ],
                        'association1' => [
                            'exclude' => true,
                            'fields'  => [
                                'association11' => [
                                    'exclude' => true,
                                    'fields'  => [
                                        'field111' => [
                                            'exclude' => true
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    '_excluded_fields' => ['association1'],
                    'fields'           => [
                        'field2'       => [
                            'depends_on' => ['association1.association11.field111']
                        ],
                        'association1' => [
                            '_excluded_fields' => ['association11'],
                            'fields'           => [
                                'association11' => [
                                    '_excluded_fields' => ['field111'],
                                    'fields'           => [
                                        'field111' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'collapsed association'                                      => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'collapse' => true,
                            'fields'   => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'collapse'        => true,
                            '_collapse_field' => 'id',
                            'fields'          => [
                                'id' => null
                            ]
                        ]
                    ]
                ]
            ],
            'collapsed association with excluded fields'                 => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'collapse' => true,
                            'fields'   => [
                                'id'   => null,
                                'name' => ['exclude' => true]
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'collapse'         => true,
                            '_collapse_field'  => 'id',
                            '_excluded_fields' => ['name'],
                            'fields'           => [
                                'id'   => null,
                                'name' => ['exclude' => true]
                            ]
                        ]
                    ]
                ]
            ],
            'collapsed association with composite identifier'            => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'collapse' => true,
                            'fields'   => [
                                'id1' => null,
                                'id2' => null
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'collapse' => true,
                            'fields'   => [
                                'id1' => null,
                                'id2' => null
                            ]
                        ]
                    ]
                ]
            ],
            'field depends on collapsed association child field'         => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1'       => [
                            'depends_on' => ['association1.field11']
                        ],
                        'association1' => [
                            'collapse' => true,
                            'fields'   => [
                                'id' => null
                            ]
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field1'       => [
                            'depends_on' => ['association1.field11']
                        ],
                        'association1' => [
                            'collapse'        => true,
                            '_collapse_field' => 'id',
                            'fields'          => [
                                'id'      => null,
                                'field11' => null
                            ]
                        ]
                    ]
                ]
            ],
            'extended association'                                       => [
                'config'         => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'association1' => [
                            'data_type' => 'association:manyToOne'
                        ]
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all'
                ]
            ]
        ];
    }
}
