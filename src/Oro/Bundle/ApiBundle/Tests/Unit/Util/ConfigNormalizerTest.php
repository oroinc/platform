<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Util\ConfigNormalizer;

class ConfigNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider normalizeConfigProvider
     */
    public function testNormalizeConfig($config, $expectedConfig)
    {
        $normalizer = new ConfigNormalizer();

        $normalizedConfig = $normalizer->normalizeConfig($config);

        $this->assertEquals($expectedConfig, $normalizedConfig);
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
                            'property_path' => '_'
                        ],
                        'field2'       => [
                            'property_path' => 'realField2'
                        ],
                        'association1' => [
                            'fields' => [
                                'association11' => [
                                    'fields' => [
                                        'field111' => [
                                            'property_path' => '_'
                                        ],
                                        'field112' => null
                                    ]
                                ],
                            ]
                        ],
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field2'       => [
                            'property_path' => 'realField2'
                        ],
                        'realField2'   => null,
                        'association1' => [
                            'fields' => [
                                'association11' => [
                                    'fields' => [
                                        'field112' => null
                                    ]
                                ],
                            ]
                        ],
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
                    'fields'           => [
                        'field1' => [
                            'exclude' => false
                        ],
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
                    'fields'           => [
                        'field1' => [
                            'exclude' => true,
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
                    'fields'           => [
                        'field1' => [
                            'exclude' => false,
                        ],
                        'field2' => [
                            'exclude'    => false,
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
                                    'exclude' => true,
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
                            'fields' => [
                                'field1' => [
                                    'exclude' => false,
                                ],
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
                            'fields' => [
                                'field11' => [
                                    'exclude' => false
                                ]
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
                    'fields'           => [
                        'association1' => [
                            'exclude' => false,
                            'fields'  => [
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
                                ],
                            ]
                        ],
                    ]
                ],
                'expectedConfig' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field2'       => [
                            'depends_on' => ['association1.association11.field111']
                        ],
                        'association1' => [
                            'exclude' => false,
                            'fields'  => [
                                'association11' => [
                                    'exclude' => false,
                                    'fields'  => [
                                        'field111' => [
                                            'exclude' => false
                                        ]
                                    ]
                                ],
                            ]
                        ],
                    ]
                ]
            ],
        ];
    }
}
