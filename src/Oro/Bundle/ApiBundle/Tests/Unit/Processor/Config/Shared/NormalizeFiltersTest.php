<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\NormalizeFilters;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class NormalizeFiltersTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var NormalizeFilters */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeFilters($this->doctrineHelper);
    }

    public function testRemoveExcludedFilters()
    {
        $filters = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => [
                    'data_type' => 'integer'
                ],
                'field2' => [
                    'data_type' => 'integer',
                    'exclude'   => true
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject([]));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type' => 'integer'
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    /**
     * @dataProvider processNotManageableEntityProvider
     */
    public function testProcessForNotManageableEntity($definition, $filters, $expectedFilters)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($definition));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);
        $this->assertEquals($expectedFilters, $this->context->getFilters()->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processNotManageableEntityProvider()
    {
        return [
            'empty'                                                          => [
                'definition'      => [],
                'filters'         => [],
                'expectedFilters' => [],
            ],
            'no child filters'                                               => [
                'definition'      => [
                    'fields' => []
                ],
                'filters'         => [
                    'fields' => [
                        'field1' => [
                            'data_type' => 'string',
                        ]
                    ]
                ],
                'expectedFilters' => [
                    'fields' => [
                        'field1' => [
                            'data_type' => 'string',
                        ]
                    ]
                ],
            ],
            'child filters'                                                  => [
                'definition'      => [
                    'fields' => [
                        'field2' => [
                            'filters' => [
                                'fields' => [
                                    'field21' => [
                                        'data_type' => 'string',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'filters'         => [
                    'fields' => [
                        'field1' => [
                            'data_type' => 'string',
                        ]
                    ]
                ],
                'expectedFilters' => [
                    'fields' => [
                        'field1'         => [
                            'data_type' => 'string',
                        ],
                        'field2.field21' => [
                            'data_type' => 'string',
                        ]
                    ]
                ]
            ],
            'child filters, fields property paths'                           => [
                'definition'      => [
                    'fields' => [
                        'field1' => [
                            'property_path' => 'realField1'
                        ],
                        'field2' => [
                            'property_path' => 'realField2',
                            'fields'        => [
                                'field21' => [
                                    'property_path' => 'realField21'
                                ]
                            ],
                            'filters'    => [
                                'fields' => [
                                    'field21' => [
                                        'data_type' => 'string',
                                    ]
                                ]
                            ]
                        ],
                        'field3' => [
                            'property_path' => 'realField3',
                            'fields'        => [
                                'field31' => [
                                    'property_path' => 'realField31',
                                ],
                                'field32' => [
                                    'property_path' => 'realField32',
                                    'fields'        => [
                                        'field321' => [
                                            'property_path' => 'realField321',
                                        ]
                                    ],
                                    'filters'    => [
                                        'fields' => [
                                            'field321' => [
                                                'data_type' => 'string',
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            'filters'    => [
                                'fields' => [
                                    'field32' => [
                                        'data_type' => 'string',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'filters'         => [
                    'fields' => [
                        'field1' => [
                            'data_type' => 'string',
                        ]
                    ]
                ],
                'expectedFilters' => [
                    'fields' => [
                        'field1'                  => [
                            'data_type'     => 'string',
                            'property_path' => 'realField1'
                        ],
                        'field2.field21'          => [
                            'data_type'     => 'string',
                            'property_path' => 'realField2.realField21'
                        ],
                        'field3.field32'          => [
                            'data_type'     => 'string',
                            'property_path' => 'realField3.realField32'
                        ],
                        'field3.field32.field321' => [
                            'data_type'     => 'string',
                            'property_path' => 'realField3.realField32.realField321'
                        ]
                    ]
                ]
            ],
            'child filters, filters property paths should not be overridden' => [
                'definition'      => [
                    'fields' => [
                        'field1' => [
                            'property_path' => 'realField1'
                        ],
                        'field2' => [
                            'property_path' => 'realField2',
                            'fields'        => [
                                'field21' => [
                                    'property_path' => 'realField21',
                                ]
                            ],
                            'filters'    => [
                                'fields' => [
                                    'field21' => [
                                        'data_type'     => 'string',
                                        'property_path' => 'filterRealField21'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'filters'         => [
                    'fields' => [
                        'field1' => [
                            'data_type'     => 'string',
                            'property_path' => 'filterRealField1'
                        ]
                    ]
                ],
                'expectedFilters' => [
                    'fields' => [
                        'field1'         => [
                            'data_type'     => 'string',
                            'property_path' => 'filterRealField1'
                        ],
                        'field2.field21' => [
                            'data_type'     => 'string',
                            'property_path' => 'realField2.filterRealField21'
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider processManageableEntityProvider
     */
    public function testProcessForManageableEntity($definition, $filters, $expectedFilters)
    {
        $rootMetadata           = $this->getClassMetadataMock();
        $toOne1Metadata         = $this->getClassMetadataMock();
        $toOne1toOne11Metadata  = $this->getClassMetadataMock();
        $toOne1toMany11Metadata = $this->getClassMetadataMock();
        $toMany1Metadata        = $this->getClassMetadataMock();

        $rootMetadata->expects($this->any())
            ->method('hasAssociation')
            ->willReturnMap(
                [
                    ['toOne1', true],
                    ['toMany1', true],
                ]
            );
        $rootMetadata->expects($this->any())
            ->method('isCollectionValuedAssociation')
            ->willReturnMap(
                [
                    ['toOne1', false],
                    ['toMany1', true],
                ]
            );

        $toOne1Metadata->expects($this->any())
            ->method('hasAssociation')
            ->willReturnMap(
                [
                    ['toOne1_toOne11', true],
                    ['toOne1_toMany11', true],
                ]
            );
        $toOne1Metadata->expects($this->any())
            ->method('isCollectionValuedAssociation')
            ->willReturnMap(
                [
                    ['toOne1_toOne11', false],
                    ['toOne1_toMany11', true],
                ]
            );

        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootMetadata);
        $this->doctrineHelper->expects($this->any())
            ->method('findEntityMetadataByPath')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, ['toOne1'], $toOne1Metadata],
                    [self::TEST_CLASS_NAME, ['toOne1', 'toOne1_toOne11'], $toOne1toOne11Metadata],
                    [self::TEST_CLASS_NAME, ['toOne1', 'toOne1_toMany11'], $toOne1toMany11Metadata],
                    [self::TEST_CLASS_NAME, ['toMany1'], $toMany1Metadata],
                ]
            );

        $this->context->setResult($this->createConfigObject($definition));
        $this->context->setFilters($this->createConfigObject($filters, ConfigUtil::FILTERS));
        $this->processor->process($this->context);
        $this->assertEquals($expectedFilters, $this->context->getFilters()->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processManageableEntityProvider()
    {
        return [
            'child filters' => [
                'definition'      => [
                    'fields' => [
                        'toOne1'  => [
                            'fields' => [
                                'toOne1_toOne11'  => [
                                    'filters' => [
                                        'fields' => [
                                            'toOne1_toOne11_field111' => [
                                                'data_type' => 'string'
                                            ]
                                        ]
                                    ]
                                ],
                                'toOne1_toMany11' => [
                                    'filters' => [
                                        'fields' => [
                                            'toOne1_toMany11_field111' => [
                                                'data_type' => 'string'
                                            ]
                                        ]
                                    ]
                                ],
                            ],
                            'filters'    => [
                                'fields' => [
                                    'toOne1_field1'   => [
                                        'data_type' => 'string'
                                    ],
                                    'toOne1_toOne11'  => [
                                        'data_type' => 'string'
                                    ],
                                    'toOne1_toMany11' => [
                                        'data_type' => 'string'
                                    ]
                                ]
                            ]
                        ],
                        'toMany1' => [
                            'filters' => [
                                'fields' => [
                                    'toMany1_field1' => [
                                        'data_type' => 'string'
                                    ]
                                ]
                            ]
                        ],
                    ]
                ],
                'filters'         => [
                    'fields' => [
                        'field1' => [
                            'data_type' => 'string'
                        ]
                    ]
                ],
                'expectedFilters' => [
                    'fields' => [
                        'field1'                                        => [
                            'data_type' => 'string'
                        ],
                        'toOne1.toOne1_field1'                          => [
                            'data_type' => 'string'
                        ],
                        'toOne1.toOne1_toOne11'                         => [
                            'data_type' => 'string'
                        ],
                        'toOne1.toOne1_toOne11.toOne1_toOne11_field111' => [
                            'data_type' => 'string'
                        ],
                    ]
                ]
            ],
        ];
    }
}
