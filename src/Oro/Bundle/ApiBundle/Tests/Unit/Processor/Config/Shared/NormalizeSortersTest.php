<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\NormalizeSorters;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class NormalizeSortersTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var NormalizeSorters */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeSorters($this->doctrineHelper);
    }

    public function testRemoveExcludedSorters()
    {
        $sorters = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclude' => true
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject([]));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null
                ]
            ],
            $this->context->getSorters()
        );
    }

    /**
     * @dataProvider processNotManageableEntityProvider
     */
    public function testProcessForNotManageableEntity($definition, $sorters, $expectedSorters)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($definition));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);
        $this->assertEquals($expectedSorters, $this->context->getSorters()->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processNotManageableEntityProvider()
    {
        return [
            'empty'                                                          => [
                'definition'      => [],
                'sorters'         => [],
                'expectedSorters' => [],
            ],
            'no child sorters'                                               => [
                'definition'      => [
                    'fields' => []
                ],
                'sorters'         => [
                    'fields' => [
                        'field1' => null
                    ]
                ],
                'expectedSorters' => [
                    'fields' => [
                        'field1' => null
                    ]
                ],
            ],
            'child sorters'                                                  => [
                'definition'      => [
                    'fields' => [
                        'field2' => [
                            'sorters' => [
                                'fields' => [
                                    'field21' => null
                                ]
                            ]
                        ]
                    ]
                ],
                'sorters'         => [
                    'fields' => [
                        'field1' => null
                    ]
                ],
                'expectedSorters' => [
                    'fields' => [
                        'field1'         => null,
                        'field2.field21' => null
                    ]
                ]
            ],
            'child sorters, fields property paths'                           => [
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
                            'sorters'    => [
                                'fields' => [
                                    'field21' => null
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
                                    'sorters'    => [
                                        'fields' => [
                                            'field321' => null
                                        ]
                                    ]
                                ]
                            ],
                            'sorters'    => [
                                'fields' => [
                                    'field32' => null
                                ]
                            ]
                        ]
                    ]
                ],
                'sorters'         => [
                    'fields' => [
                        'field1' => null
                    ]
                ],
                'expectedSorters' => [
                    'fields' => [
                        'field1'                  => [
                            'property_path' => 'realField1'
                        ],
                        'field2.field21'          => [
                            'property_path' => 'realField2.realField21'
                        ],
                        'field3.field32'          => [
                            'property_path' => 'realField3.realField32'
                        ],
                        'field3.field32.field321' => [
                            'property_path' => 'realField3.realField32.realField321'
                        ]
                    ]
                ]
            ],
            'child sorters, sorters property paths should not be overridden' => [
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
                            'sorters'    => [
                                'fields' => [
                                    'field21' => [
                                        'property_path' => 'sorterRealField21'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'sorters'         => [
                    'fields' => [
                        'field1' => [
                            'property_path' => 'sorterRealField1'
                        ]
                    ]
                ],
                'expectedSorters' => [
                    'fields' => [
                        'field1'         => [
                            'property_path' => 'sorterRealField1'
                        ],
                        'field2.field21' => [
                            'property_path' => 'realField2.sorterRealField21'
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider processManageableEntityProvider
     */
    public function testProcessForManageableEntity($definition, $sorters, $expectedSorters)
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
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);
        $this->assertEquals($expectedSorters, $this->context->getSorters()->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processManageableEntityProvider()
    {
        return [
            'child sorters' => [
                'definition'      => [
                    'fields' => [
                        'toOne1'  => [
                            'fields' => [
                                'toOne1_toOne11'  => [
                                    'sorters' => [
                                        'fields' => [
                                            'toOne1_toOne11_field111' => null
                                        ]
                                    ]
                                ],
                                'toOne1_toMany11' => [
                                    'sorters' => [
                                        'fields' => [
                                            'toOne1_toMany11_field111' => null
                                        ]
                                    ]
                                ],
                            ],
                            'sorters'    => [
                                'fields' => [
                                    'toOne1_field1'   => null,
                                    'toOne1_toOne11'  => null,
                                    'toOne1_toMany11' => null
                                ]
                            ]
                        ],
                        'toMany1' => [
                            'sorters' => [
                                'fields' => [
                                    'toMany1_field1' => null
                                ]
                            ]
                        ],
                    ]
                ],
                'sorters'         => [
                    'fields' => [
                        'field1' => null
                    ]
                ],
                'expectedSorters' => [
                    'fields' => [
                        'field1'                                        => null,
                        'toOne1.toOne1_field1'                          => null,
                        'toOne1.toOne1_toOne11'                         => null,
                        'toOne1.toOne1_toOne11.toOne1_toOne11_field111' => null,
                    ]
                ]
            ],
        ];
    }
}
