<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\SetMaxRelatedEntities;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class SetMaxRelatedEntitiesTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var SetMaxRelatedEntities */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new SetMaxRelatedEntities(
            $this->doctrineHelper
        );
    }

    public function testProcessForEmptyConfig()
    {
        $config = [];

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig([], $configObject);
    }

    public function testProcessForNotCompletedConfig()
    {
        $config = [
            'fields' => [
                'field1' => null
            ]
        ];

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null
                ]
            ],
            $configObject
        );
    }

    public function testProcessWithoutLimit()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field21' => null,
                        'field22' => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'field221' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            $config,
            $configObject
        );
    }

    public function testProcessForNotManageableEntityWithoutTargetOptions()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field21' => null,
                        'field22' => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'field221' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $limit = 100;

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $configObject = $this->createConfigObject($config);
        $this->context->setMaxRelatedEntities($limit);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            $config,
            $configObject
        );
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'target_class'     => 'Test\Target',
                    'target_type'      => 'to-many',
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field21' => null,
                        'field22' => [
                            'target_class'     => 'Test\Target',
                            'target_type'      => 'to-many',
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'field221' => null
                            ]
                        ],
                        'field23' => [
                            'target_class'     => 'Test\Target',
                            'target_type'      => 'to-many',
                            'exclusion_policy' => 'all',
                            'max_results'      => -1,
                            'fields'           => [
                                'field231' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $limit = 100;

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $configObject = $this->createConfigObject($config);
        $this->context->setMaxRelatedEntities($limit);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null,
                    'field2' => [
                        'target_class'     => 'Test\Target',
                        'target_type'      => 'to-many',
                        'exclusion_policy' => 'all',
                        'max_results'      => $limit,
                        'fields'           => [
                            'field21' => null,
                            'field22' => [
                                'target_class'     => 'Test\Target',
                                'target_type'      => 'to-many',
                                'exclusion_policy' => 'all',
                                'max_results'      => $limit,
                                'fields'           => [
                                    'field221' => null
                                ]
                            ],
                            'field23' => [
                                'target_class'     => 'Test\Target',
                                'target_type'      => 'to-many',
                                'exclusion_policy' => 'all',
                                'fields'           => [
                                    'field231' => null
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $configObject
        );
    }

    public function testProcessForNotManageableEntityWithParentToOneAndChildToMany()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'target_class'     => 'Test\Target',
                    'target_type'      => 'to-one',
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field21' => null,
                        'field22' => [
                            'target_class'     => 'Test\Target',
                            'target_type'      => 'to-many',
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'field221' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $limit = 100;

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $configObject = $this->createConfigObject($config);
        $this->context->setMaxRelatedEntities($limit);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null,
                    'field2' => [
                        'target_class'     => 'Test\Target',
                        'target_type'      => 'to-one',
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'field21' => null,
                            'field22' => [
                                'target_class'     => 'Test\Target',
                                'target_type'      => 'to-many',
                                'exclusion_policy' => 'all',
                                'max_results'      => $limit,
                                'fields'           => [
                                    'field221' => null
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $configObject
        );
    }

    public function testProcessForNotManageableEntityWhenToMayAssociationShouldBeRepresentedAsField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => [
                    'data_type'        => 'array',
                    'target_class'     => 'Test\Target',
                    'target_type'      => 'to-many',
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field11' => null
                    ]
                ]
            ]
        ];
        $limit = 100;

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $configObject = $this->createConfigObject($config);
        $this->context->setMaxRelatedEntities($limit);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'        => 'array',
                        'target_class'     => 'Test\Target',
                        'target_type'      => 'to-many',
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'field11' => null
                        ]
                    ]
                ]
            ],
            $configObject
        );
    }

    public function testProcessForManageableEntityWhenToMayAssociationShouldBeRepresentedAsField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => [
                    'data_type'        => 'array',
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field11' => null
                    ]
                ]
            ]
        ];
        $limit = 100;

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('hasAssociation')
            ->willReturnMap([['field1', true]]);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('field1')
            ->willReturn('Test\Field1Target');
        $rootEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('field1')
            ->willReturn(true);

        $field1TargetEntityMetadata = $this->getClassMetadataMock('Test\Field1Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Field1Target', true, $field1TargetEntityMetadata]
                ]
            );

        $configObject = $this->createConfigObject($config);
        $this->context->setMaxRelatedEntities($limit);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'data_type'        => 'array',
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'field11' => null
                        ]
                    ]
                ]
            ],
            $configObject
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForManageableEntityWithToManyAssociations()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field21' => null,
                        'field22' => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'field221' => null
                            ]
                        ],
                        'field23' => [
                            'exclusion_policy' => 'all',
                            'max_results'      => -1,
                            'fields'           => [
                                'field231' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $limit = 100;

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('hasAssociation')
            ->willReturnMap([['field2', true]]);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('field2')
            ->willReturn('Test\Field2Target');
        $rootEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('field2')
            ->willReturn(true);

        $field2TargetEntityMetadata = $this->getClassMetadataMock('Test\Field2Target');
        $field2TargetEntityMetadata->expects(self::any())
            ->method('hasAssociation')
            ->willReturnMap([['field22', true], ['field23', true]]);
        $field2TargetEntityMetadata->expects(self::any())
            ->method('getAssociationTargetClass')
            ->willReturnMap([['field22', 'Test\Field22Target'], ['field23', 'Test\Field23Target']]);
        $field2TargetEntityMetadata->expects(self::any())
            ->method('isCollectionValuedAssociation')
            ->willReturnMap([['field22', true], ['field23', true]]);

        $field22TargetEntityMetadata = $this->getClassMetadataMock('Test\Field22Target');
        $field23TargetEntityMetadata = $this->getClassMetadataMock('Test\Field23Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(4))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Field2Target', true, $field2TargetEntityMetadata],
                    ['Test\Field22Target', true, $field22TargetEntityMetadata],
                    ['Test\Field23Target', true, $field23TargetEntityMetadata]
                ]
            );

        $configObject = $this->createConfigObject($config);
        $this->context->setMaxRelatedEntities($limit);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null,
                    'field2' => [
                        'exclusion_policy' => 'all',
                        'max_results'      => $limit,
                        'fields'           => [
                            'field21' => null,
                            'field22' => [
                                'exclusion_policy' => 'all',
                                'max_results'      => $limit,
                                'fields'           => [
                                    'field221' => null
                                ]
                            ],
                            'field23' => [
                                'exclusion_policy' => 'all',
                                'fields'           => [
                                    'field231' => null
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $configObject
        );
    }

    public function testProcessForManageableEntityWithParentToOneAndChildToMany()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field21' => null,
                        'field22' => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'field221' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $limit = 100;

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('hasAssociation')
            ->willReturnMap([['field2', true]]);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('field2')
            ->willReturn('Test\Field2Target');
        $rootEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('field2')
            ->willReturn(false);

        $field2TargetEntityMetadata = $this->getClassMetadataMock('Test\Field2Target');
        $field2TargetEntityMetadata->expects(self::any())
            ->method('hasAssociation')
            ->willReturnMap([['field22', true]]);
        $field2TargetEntityMetadata->expects(self::any())
            ->method('getAssociationTargetClass')
            ->with('field22')
            ->willReturn('Test\Field22Target');
        $field2TargetEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('field22')
            ->willReturn(true);

        $field22TargetEntityMetadata = $this->getClassMetadataMock('Test\Field22Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Field2Target', true, $field2TargetEntityMetadata],
                    ['Test\Field22Target', true, $field22TargetEntityMetadata]
                ]
            );

        $configObject = $this->createConfigObject($config);
        $this->context->setMaxRelatedEntities($limit);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null,
                    'field2' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'field21' => null,
                            'field22' => [
                                'exclusion_policy' => 'all',
                                'max_results'      => $limit,
                                'fields'           => [
                                    'field221' => null
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $configObject
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForManageableEntityAndAssociationsWithPropertyPath()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclusion_policy' => 'all',
                    'property_path'    => 'realField2',
                    'fields'           => [
                        'field21' => null,
                        'field22' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'realField22',
                            'fields'           => [
                                'field221' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $limit = 100;

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('hasAssociation')
            ->willReturnMap([['realField2', true]]);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('realField2')
            ->willReturn('Test\Field2Target');
        $rootEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('realField2')
            ->willReturn(true);

        $field2TargetEntityMetadata = $this->getClassMetadataMock('Test\Field2Target');
        $field2TargetEntityMetadata->expects(self::any())
            ->method('hasAssociation')
            ->willReturnMap([['realField22', true]]);
        $field2TargetEntityMetadata->expects(self::any())
            ->method('getAssociationTargetClass')
            ->with('realField22')
            ->willReturn('Test\Field22Target');
        $field2TargetEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('realField22')
            ->willReturn(true);

        $field22TargetEntityMetadata = $this->getClassMetadataMock('Test\Field22Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Field2Target', true, $field2TargetEntityMetadata],
                    ['Test\Field22Target', true, $field22TargetEntityMetadata]
                ]
            );

        $configObject = $this->createConfigObject($config);
        $this->context->setMaxRelatedEntities($limit);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null,
                    'field2' => [
                        'exclusion_policy' => 'all',
                        'property_path'    => 'realField2',
                        'max_results'      => $limit,
                        'fields'           => [
                            'field21' => null,
                            'field22' => [
                                'exclusion_policy' => 'all',
                                'property_path'    => 'realField22',
                                'max_results'      => $limit,
                                'fields'           => [
                                    'field221' => null
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $configObject
        );
    }

    public function testProcessForManageableEntityAndAssociationsWithPropertyPathToChildEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclusion_policy' => 'all',
                    'property_path'    => 'field22.field221',
                    'fields'           => [
                        'field22' => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'field221' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $limit = 100;

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $configObject = $this->createConfigObject($config);
        $this->context->setMaxRelatedEntities($limit);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null,
                    'field2' => [
                        'exclusion_policy' => 'all',
                        'property_path'    => 'field22.field221',
                        'fields'           => [
                            'field22' => [
                                'exclusion_policy' => 'all',
                                'fields'           => [
                                    'field221' => null
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $configObject
        );
    }
}
