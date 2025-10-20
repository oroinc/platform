<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Exception\NotSupportedConfigOperationException;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\GetConfig\FilterFieldsByExtra;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class FilterFieldsByExtraTest extends ConfigProcessorTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private ValueNormalizer&MockObject $valueNormalizer;
    private FilterFieldsByExtra $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $this->processor = new FilterFieldsByExtra(
            $this->doctrineHelper,
            $this->valueNormalizer
        );
    }

    public function testProcessForNotCompletedConfig(): void
    {
        $config = [
            'fields' => [
                'field1' => null
            ]
        ];

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoFields(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => []
        ];

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForDisabledFieldset(): void
    {
        $this->expectException(NotSupportedConfigOperationException::class);
        $this->expectExceptionMessage(
            'Requested unsupported operation "filter_fields" when building config for "Test\Class".'
        );

        $config = [
            'exclusion_policy' => 'all',
            'disable_fieldset' => true,
            'fields' => [
                'field1' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

    public function testProcessForDisabledFieldsetForEntityIdentifier(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'disable_fieldset' => true,
            'fields' => [
                'id' => null
            ]
        ];

        $this->context->setClassName(EntityIdentifier::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForNotManageableEntity(): void
    {
        $config = [
            'identifier_field_names' => ['id'],
            'exclusion_policy' => 'all',
            'fields' => [
                'id' => null,
                'field1' => null,
                'field2' => null,
                'association1' => [
                    'identifier_field_names' => ['id'],
                    'target_class' => 'Test\Association1Target',
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null,
                        'field2' => null
                    ]
                ],
                'association2' => [
                    'identifier_field_names' => ['id'],
                    'target_class' => 'Test\Association2Target',
                    'exclusion_policy' => 'all',
                    'property_path' => 'realAssociation2',
                    'fields' => [
                        'id' => null,
                        'field1' => null,
                        'field2' => null
                    ]
                ],
                'association3' => [
                    'identifier_field_names' => ['id'],
                    'target_class' => 'Test\Association3Target',
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null
                    ]
                ]
            ]
        ];

        $this->context->setExtras([
            new FilterFieldsConfigExtra([
                'primary_entity' => ['field1', 'association1', 'association2', 'association3'],
                'association_1_entity' => ['id', 'field1'],
                'association_2_entity' => ['field2']
            ])
        ]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityMetadataForClass');

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(3))
            ->method('normalizeValue')
            ->willReturnMap([
                ['primary_entity', DataType::ENTITY_CLASS, $requestType, false, false, [], self::TEST_CLASS_NAME],
                [
                    'association_1_entity',
                    DataType::ENTITY_CLASS,
                    $requestType,
                    false,
                    false,
                    [],
                    'Test\Association1Target'
                ],
                [
                    'association_2_entity',
                    DataType::ENTITY_CLASS,
                    $requestType,
                    false,
                    false,
                    [],
                    'Test\Association2Target'
                ]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'exclusion_policy' => 'all',
                'fields' => [
                    'id' => null,
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'association1' => [
                        'identifier_field_names' => ['id'],
                        'target_class' => 'Test\Association1Target',
                        'exclusion_policy' => 'all',
                        'fields' => [
                            'id' => null,
                            'field1' => null,
                            'field2' => [
                                'exclude' => true
                            ]
                        ]
                    ],
                    'association2' => [
                        'identifier_field_names' => ['id'],
                        'target_class' => 'Test\Association2Target',
                        'exclusion_policy' => 'all',
                        'property_path' => 'realAssociation2',
                        'fields' => [
                            'id' => null,
                            'field1' => [
                                'exclude' => true
                            ],
                            'field2' => null
                        ]
                    ],
                    'association3' => [
                        'identifier_field_names' => ['id'],
                        'target_class' => 'Test\Association3Target',
                        'exclusion_policy' => 'all',
                        'fields' => [
                            'id' => null,
                            'field1' => null
                        ]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForManageableEntity(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'id' => null,
                'field1' => null,
                'field2' => null,
                'association1' => [
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null,
                        'field2' => null,
                        '__class__' => [
                            'meta_property' => true
                        ],
                        'requiredMetaProperty1' => [
                            'meta_property' => true,
                            'property_path' => '__requited_meta_property_1__'
                        ],
                        '__requited_meta_property_2__' => [
                            'meta_property' => true,
                            'property_path' => 'requiredMetaProperty2'
                        ],
                        'metaProperty1' => [
                            'meta_property' => true
                        ],
                        'metaProperty2' => [
                            'meta_property' => true,
                            'property_path' => 'meta_property_2'
                        ]
                    ]
                ],
                'association2' => [
                    'exclusion_policy' => 'all',
                    'property_path' => 'realAssociation2',
                    'fields' => [
                        'id' => null,
                        'field1' => null,
                        'field2' => null
                    ]
                ],
                'association3' => [
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null
                    ]
                ]
            ]
        ];

        $this->context->setExtras([
            new FilterFieldsConfigExtra([
                'primary_entity' => ['field1', 'association1', 'association2', 'association3'],
                'association_1_entity' => ['id', 'field1'],
                'association_2_entity' => ['field2']
            ])
        ]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::exactly(3))
            ->method('hasAssociation')
            ->willReturnMap([
                ['association1', true],
                ['realAssociation2', true],
                ['association3', true]
            ]);
        $rootEntityMetadata->expects(self::exactly(3))
            ->method('getAssociationTargetClass')
            ->willReturnMap([
                ['association1', 'Test\Association1Target'],
                ['realAssociation2', 'Test\Association2Target'],
                ['association3', 'Test\Association3Target']
            ]);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $association2Metadata = $this->getClassMetadataMock('Test\Association2Target');
        $association2Metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $association3Metadata = $this->getClassMetadataMock('Test\Association3Target');
        $association3Metadata->expects(self::never())
            ->method('getIdentifierFieldNames');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(4))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata],
                ['Test\Association2Target', true, $association2Metadata],
                ['Test\Association3Target', true, $association3Metadata]
            ]);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(3))
            ->method('normalizeValue')
            ->willReturnMap([
                ['primary_entity', DataType::ENTITY_CLASS, $requestType, false, false, [], self::TEST_CLASS_NAME],
                [
                    'association_1_entity',
                    DataType::ENTITY_CLASS,
                    $requestType,
                    false,
                    false,
                    [],
                    'Test\Association1Target'
                ],
                [
                    'association_2_entity',
                    DataType::ENTITY_CLASS,
                    $requestType,
                    false,
                    false,
                    [],
                    'Test\Association2Target'
                ]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields' => [
                    'id' => null,
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'fields' => [
                            'id' => null,
                            'field1' => null,
                            'field2' => [
                                'exclude' => true
                            ],
                            '__class__' => [
                                'meta_property' => true
                            ],
                            'requiredMetaProperty1' => [
                                'meta_property' => true,
                                'property_path' => '__requited_meta_property_1__'
                            ],
                            '__requited_meta_property_2__' => [
                                'meta_property' => true,
                                'property_path' => 'requiredMetaProperty2'
                            ],
                            'metaProperty1' => [
                                'meta_property' => true,
                                'exclude' => true
                            ],
                            'metaProperty2' => [
                                'meta_property' => true,
                                'property_path' => 'meta_property_2',
                                'exclude' => true
                            ]
                        ]
                    ],
                    'association2' => [
                        'exclusion_policy' => 'all',
                        'property_path' => 'realAssociation2',
                        'fields' => [
                            'id' => null,
                            'field1' => [
                                'exclude' => true
                            ],
                            'field2' => null
                        ]
                    ],
                    'association3' => [
                        'exclusion_policy' => 'all',
                        'fields' => [
                            'id' => null,
                            'field1' => null
                        ]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenTargetEntityUsesTableInheritance(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'association1' => [
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null,
                        'field2' => null
                    ]
                ]
            ]
        ];

        $this->context->setExtras([
            new FilterFieldsConfigExtra([
                'primary_entity' => ['field1', 'association1'],
                'association_1_1_entity' => ['id', 'field1']
            ])
        ]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('association1')
            ->willReturn('Test\Association1Target');

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $association1Metadata->subClasses = ['Test\Association1Target1', 'Test\Association1Target2'];
        $association1Metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                ['primary_entity', DataType::ENTITY_CLASS, $requestType, false, false, [], self::TEST_CLASS_NAME],
                [
                    'association_1_1_entity',
                    DataType::ENTITY_CLASS,
                    $requestType,
                    false,
                    false,
                    [],
                    'Test\Association1Target'
                ]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields' => [
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'fields' => [
                            'id' => null,
                            'field1' => null,
                            'field2' => [
                                'exclude' => true
                            ]
                        ]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForRenamedIdentifierFieldOfManageableEntity(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'id' => ['property_path' => 'realId'],
                'field1' => null,
                'association1' => [
                    'exclusion_policy' => 'all',
                    'fields' => ['id' => null]
                ],
                'association2' => [
                    'exclusion_policy' => 'all',
                    'fields' => ['id' => null]
                ]
            ]
        ];

        $this->context->setExtras([new FilterFieldsConfigExtra(['primary_entity' => ['association1']])]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['realId']);
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('hasAssociation')
            ->willReturnMap([
                ['association1', true],
                ['association2', true]
            ]);
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('getAssociationTargetClass')
            ->willReturnMap([
                ['association1', 'Test\Association1Target'],
                ['association2', 'Test\Association2Target']
            ]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $this->getClassMetadataMock('Test\Association1Target')],
                ['Test\Association2Target', true, $this->getClassMetadataMock('Test\Association2Target')]
            ]);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('primary_entity', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false, [])
            ->willReturn(self::TEST_CLASS_NAME);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields' => [
                    'id' => ['property_path' => 'realId'],
                    'field1' => ['exclude' => true],
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'fields' => ['id' => null]
                    ],
                    'association2' => [
                        'exclusion_policy' => 'all',
                        'exclude' => true,
                        'fields' => ['id' => null]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForRenamedIdentifierFieldOfManageableEntityAndConfigHasIdentifierFields(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'identifier_field_names' => ['id'],
            'fields' => [
                'id' => ['property_path' => 'realId'],
                'field1' => null,
                'association1' => [
                    'exclusion_policy' => 'all',
                    'fields' => ['id' => null]
                ],
                'association2' => [
                    'exclusion_policy' => 'all',
                    'fields' => ['id' => null]
                ]
            ]
        ];

        $this->context->setExtras([new FilterFieldsConfigExtra(['primary_entity' => ['association1']])]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::never())
            ->method('getIdentifierFieldNames');
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('hasAssociation')
            ->willReturnMap([
                ['association1', true],
                ['association2', true]
            ]);
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('getAssociationTargetClass')
            ->willReturnMap([
                ['association1', 'Test\Association1Target'],
                ['association2', 'Test\Association2Target']
            ]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $this->getClassMetadataMock('Test\Association1Target')],
                ['Test\Association2Target', true, $this->getClassMetadataMock('Test\Association2Target')]
            ]);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('primary_entity', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false, [])
            ->willReturn(self::TEST_CLASS_NAME);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'identifier_field_names' => ['id'],
                'fields' => [
                    'id' => ['property_path' => 'realId'],
                    'field1' => ['exclude' => true],
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'fields' => ['id' => null]
                    ],
                    'association2' => [
                        'exclusion_policy' => 'all',
                        'exclude' => true,
                        'fields' => ['id' => null]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForSubresourceWithDisabledFieldset(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'disable_fieldset' => true,
            'fields' => [
                'id' => null,
                'association1' => [
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null
                    ]
                ]
            ]
        ];
        $this->context->setExtras([
            new FilterFieldsConfigExtra(['primary_entity' => ['association1']])
        ]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('association1')
            ->willReturn('Test\Association1Target');

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('primary_entity', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false, [])
            ->willReturn(self::TEST_CLASS_NAME);

        $this->context->setParentClassName(self::TEST_CLASS_NAME);
        $this->context->setAssociationName('association1');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'disable_fieldset' => true,
                'fields' => [
                    'id' => null,
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'fields' => [
                            'id' => null,
                            'field1' => null
                        ]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForSubresourceWithDisabledFieldsetAndAdditionalFieldsFilter(): void
    {
        $this->expectException(NotSupportedConfigOperationException::class);
        $this->expectExceptionMessage(
            'Requested unsupported operation "filter_fields" when building config for "Test\Class".'
        );

        $config = [
            'exclusion_policy' => 'all',
            'disable_fieldset' => true,
            'fields' => [
                'id' => null,
                'association1' => [
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null
                    ]
                ]
            ]
        ];
        $this->context->setExtras([
            new FilterFieldsConfigExtra(['primary_entity' => ['association1', 'association2']])
        ]);

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityMetadataForClass');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('primary_entity', DataType::ENTITY_CLASS, $this->context->getRequestType(), false, false, [])
            ->willReturn(self::TEST_CLASS_NAME);

        $this->context->setParentClassName(self::TEST_CLASS_NAME);
        $this->context->setAssociationName('association1');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenEmptyValueForNotManageableEntity(): void
    {
        $config = [
            'identifier_field_names' => ['id'],
            'exclusion_policy' => 'all',
            'fields' => [
                'id' => null,
                'field1' => null,
                'field2' => null,
                'association1' => [
                    'identifier_field_names' => ['id'],
                    'target_class' => 'Test\Association1Target',
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null,
                        'field2' => null
                    ]
                ],
                'association2' => [
                    'identifier_field_names' => ['id'],
                    'target_class' => 'Test\Association2Target',
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null
                    ]
                ]
            ]
        ];

        $this->context->setExtras([
            new FilterFieldsConfigExtra([
                'primary_entity' => [],
                'association_1_entity' => []
            ])
        ]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityMetadataForClass');

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                ['primary_entity', DataType::ENTITY_CLASS, $requestType, false, false, [], self::TEST_CLASS_NAME],
                [
                    'association_1_entity',
                    DataType::ENTITY_CLASS,
                    $requestType,
                    false,
                    false,
                    [],
                    'Test\Association1Target'
                ]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'exclusion_policy' => 'all',
                'fields' => [
                    'id' => null,
                    'field1' => [
                        'exclude' => true
                    ],
                    'field2' => [
                        'exclude' => true
                    ],
                    'association1' => [
                        'identifier_field_names' => ['id'],
                        'target_class' => 'Test\Association1Target',
                        'exclusion_policy' => 'all',
                        'exclude' => true,
                        'fields' => [
                            'id' => null,
                            'field1' => [
                                'exclude' => true
                            ],
                            'field2' => [
                                'exclude' => true
                            ]
                        ]
                    ],
                    'association2' => [
                        'identifier_field_names' => ['id'],
                        'target_class' => 'Test\Association2Target',
                        'exclusion_policy' => 'all',
                        'exclude' => true,
                        'fields' => [
                            'id' => null,
                            'field1' => null
                        ]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenEmptyValueForManageableEntity(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'id' => null,
                'field1' => null,
                'field2' => null,
                'association1' => [
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null,
                        'field2' => null,
                        '__class__' => [
                            'meta_property' => true
                        ],
                        'requiredMetaProperty1' => [
                            'meta_property' => true,
                            'property_path' => '__requited_meta_property_1__'
                        ],
                        '__requited_meta_property_2__' => [
                            'meta_property' => true,
                            'property_path' => 'requiredMetaProperty2'
                        ],
                        'metaProperty1' => [
                            'meta_property' => true
                        ],
                        'metaProperty2' => [
                            'meta_property' => true,
                            'property_path' => 'meta_property_2'
                        ]
                    ]
                ],
                'association2' => [
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null
                    ]
                ]
            ]
        ];

        $this->context->setExtras([
            new FilterFieldsConfigExtra([
                'primary_entity' => [],
                'association_1_entity' => []
            ])
        ]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('hasAssociation')
            ->willReturnMap([
                ['association1', true],
                ['association2', true]
            ]);
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('getAssociationTargetClass')
            ->willReturnMap([
                ['association1', 'Test\Association1Target'],
                ['association2', 'Test\Association2Target']
            ]);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $association2Metadata = $this->getClassMetadataMock('Test\Association2Target');
        $association2Metadata->expects(self::never())
            ->method('getIdentifierFieldNames');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata],
                ['Test\Association2Target', true, $association2Metadata]
            ]);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                ['primary_entity', DataType::ENTITY_CLASS, $requestType, false, false, [], self::TEST_CLASS_NAME],
                [
                    'association_1_entity',
                    DataType::ENTITY_CLASS,
                    $requestType,
                    false,
                    false,
                    [],
                    'Test\Association1Target'
                ]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields' => [
                    'id' => null,
                    'field1' => [
                        'exclude' => true
                    ],
                    'field2' => [
                        'exclude' => true
                    ],
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'exclude' => true,
                        'fields' => [
                            'id' => null,
                            'field1' => [
                                'exclude' => true
                            ],
                            'field2' => [
                                'exclude' => true
                            ],
                            '__class__' => [
                                'meta_property' => true
                            ],
                            'requiredMetaProperty1' => [
                                'meta_property' => true,
                                'property_path' => '__requited_meta_property_1__'
                            ],
                            '__requited_meta_property_2__' => [
                                'meta_property' => true,
                                'property_path' => 'requiredMetaProperty2'
                            ],
                            'metaProperty1' => [
                                'meta_property' => true,
                                'exclude' => true
                            ],
                            'metaProperty2' => [
                                'meta_property' => true,
                                'property_path' => 'meta_property_2',
                                'exclude' => true
                            ]
                        ]
                    ],
                    'association2' => [
                        'exclusion_policy' => 'all',
                        'exclude' => true,
                        'fields' => [
                            'id' => null,
                            'field1' => null
                        ]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenNullValueForManageableEntity(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'id' => null,
                'field1' => null,
                'field2' => null,
                'association1' => [
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null,
                        'field2' => null,
                        '__class__' => [
                            'meta_property' => true
                        ],
                        'requiredMetaProperty1' => [
                            'meta_property' => true,
                            'property_path' => '__requited_meta_property_1__'
                        ],
                        '__requited_meta_property_2__' => [
                            'meta_property' => true,
                            'property_path' => 'requiredMetaProperty2'
                        ],
                        'metaProperty1' => [
                            'meta_property' => true
                        ],
                        'metaProperty2' => [
                            'meta_property' => true,
                            'property_path' => 'meta_property_2'
                        ]
                    ]
                ],
                'association2' => [
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null
                    ]
                ]
            ]
        ];

        $this->context->setExtras([
            new FilterFieldsConfigExtra([
                'primary_entity' => null,
                'association_1_entity' => null
            ])
        ]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::never())
            ->method('getIdentifierFieldNames');
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('hasAssociation')
            ->willReturnMap([
                ['association1', true],
                ['association2', true]
            ]);
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('getAssociationTargetClass')
            ->willReturnMap([
                ['association1', 'Test\Association1Target'],
                ['association2', 'Test\Association2Target']
            ]);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->expects(self::never())
            ->method('getIdentifierFieldNames');

        $association2Metadata = $this->getClassMetadataMock('Test\Association2Target');
        $association2Metadata->expects(self::never())
            ->method('getIdentifierFieldNames');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata],
                ['Test\Association2Target', true, $association2Metadata]
            ]);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                ['primary_entity', DataType::ENTITY_CLASS, $requestType, false, false, [], self::TEST_CLASS_NAME],
                [
                    'association_1_entity',
                    DataType::ENTITY_CLASS,
                    $requestType,
                    false,
                    false,
                    [],
                    'Test\Association1Target'
                ]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields' => [
                    'id' => null,
                    'field1' => null,
                    'field2' => null,
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'fields' => [
                            'id' => null,
                            'field1' => null,
                            'field2' => null,
                            '__class__' => [
                                'meta_property' => true
                            ],
                            'requiredMetaProperty1' => [
                                'meta_property' => true,
                                'property_path' => '__requited_meta_property_1__'
                            ],
                            '__requited_meta_property_2__' => [
                                'meta_property' => true,
                                'property_path' => 'requiredMetaProperty2'
                            ],
                            'metaProperty1' => [
                                'meta_property' => true
                            ],
                            'metaProperty2' => [
                                'meta_property' => true,
                                'property_path' => 'meta_property_2'
                            ]
                        ]
                    ],
                    'association2' => [
                        'exclusion_policy' => 'all',
                        'fields' => [
                            'id' => null,
                            'field1' => null
                        ]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenEmptyValueWhenTargetEntityUsesTableInheritance(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'association1' => [
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null,
                        'field2' => null
                    ]
                ]
            ]
        ];

        $this->context->setExtras([
            new FilterFieldsConfigExtra([
                'primary_entity' => ['field1', 'association1'],
                'association_1_1_entity' => []
            ])
        ]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('association1')
            ->willReturn('Test\Association1Target');

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $association1Metadata->subClasses = ['Test\Association1Target1', 'Test\Association1Target2'];
        $association1Metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                ['primary_entity', DataType::ENTITY_CLASS, $requestType, false, false, [], self::TEST_CLASS_NAME],
                [
                    'association_1_1_entity',
                    DataType::ENTITY_CLASS,
                    $requestType,
                    false,
                    false,
                    [],
                    'Test\Association1Target'
                ]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields' => [
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'fields' => [
                            'id' => null,
                            'field1' => [
                                'exclude' => true
                            ],
                            'field2' => [
                                'exclude' => true
                            ]
                        ]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenNullValueWhenTargetEntityUsesTableInheritance(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'association1' => [
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null,
                        'field2' => null
                    ]
                ]
            ]
        ];

        $this->context->setExtras([
            new FilterFieldsConfigExtra([
                'primary_entity' => ['field1', 'association1'],
                'association_1_1_entity' => null
            ])
        ]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('association1')
            ->willReturn('Test\Association1Target');

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $association1Metadata->subClasses = ['Test\Association1Target1', 'Test\Association1Target2'];
        $association1Metadata->expects(self::never())
            ->method('getIdentifierFieldNames');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                ['primary_entity', DataType::ENTITY_CLASS, $requestType, false, false, [], self::TEST_CLASS_NAME],
                [
                    'association_1_1_entity',
                    DataType::ENTITY_CLASS,
                    $requestType,
                    false,
                    false,
                    [],
                    'Test\Association1Target'
                ]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields' => [
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'fields' => [
                            'id' => null,
                            'field1' => null,
                            'field2' => null
                        ]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForNotManageableEntityWhenThereIsFieldsFilterWithNullValue(): void
    {
        $config = [
            'identifier_field_names' => ['id'],
            'exclusion_policy' => 'all',
            'fields' => [
                'id' => null,
                'field1' => null,
                'association1' => [
                    'identifier_field_names' => ['id'],
                    'target_class' => 'Test\Association1Target',
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null,
                        'field2' => null
                    ]
                ]
            ]
        ];

        $this->context->setExtras([
            new FilterFieldsConfigExtra([
                'primary_entity' => null,
                'association_1_entity' => ['id', 'field1']
            ])
        ]);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                ['primary_entity', DataType::ENTITY_CLASS, $requestType, false, false, [], self::TEST_CLASS_NAME],
                [
                    'association_1_entity',
                    DataType::ENTITY_CLASS,
                    $requestType,
                    false,
                    false,
                    [],
                    'Test\Association1Target'
                ]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'exclusion_policy' => 'all',
                'fields' => [
                    'id' => null,
                    'field1' => null,
                    'association1' => [
                        'identifier_field_names' => ['id'],
                        'target_class' => 'Test\Association1Target',
                        'exclusion_policy' => 'all',
                        'fields' => [
                            'id' => null,
                            'field1' => null,
                            'field2' => [
                                'exclude' => true
                            ]
                        ]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForManageableEntityWhenThereIsFieldsFilterWithNullValue(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'id' => null,
                'field1' => null,
                'association1' => [
                    'exclusion_policy' => 'all',
                    'fields' => [
                        'id' => null,
                        'field1' => null,
                        'field2' => null,
                        '__class__' => [
                            'meta_property' => true
                        ],
                        'requiredMetaProperty1' => [
                            'meta_property' => true,
                            'property_path' => '__requited_meta_property_1__'
                        ],
                        '__requited_meta_property_2__' => [
                            'meta_property' => true,
                            'property_path' => 'requiredMetaProperty2'
                        ],
                        'metaProperty1' => [
                            'meta_property' => true
                        ],
                        'metaProperty2' => [
                            'meta_property' => true,
                            'property_path' => 'meta_property_2'
                        ]
                    ]
                ]
            ]
        ];

        $this->context->setExtras([
            new FilterFieldsConfigExtra([
                'primary_entity' => null,
                'association_1_entity' => ['id', 'field1']
            ])
        ]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::never())
            ->method('getIdentifierFieldNames');
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('association1')
            ->willReturn('Test\Association1Target');

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                ['primary_entity', DataType::ENTITY_CLASS, $requestType, false, false, [], self::TEST_CLASS_NAME],
                [
                    'association_1_entity',
                    DataType::ENTITY_CLASS,
                    $requestType,
                    false,
                    false,
                    [],
                    'Test\Association1Target'
                ]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields' => [
                    'id' => null,
                    'field1' => null,
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'fields' => [
                            'id' => null,
                            'field1' => null,
                            'field2' => [
                                'exclude' => true
                            ],
                            '__class__' => [
                                'meta_property' => true
                            ],
                            'requiredMetaProperty1' => [
                                'meta_property' => true,
                                'property_path' => '__requited_meta_property_1__'
                            ],
                            '__requited_meta_property_2__' => [
                                'meta_property' => true,
                                'property_path' => 'requiredMetaProperty2'
                            ],
                            'metaProperty1' => [
                                'meta_property' => true,
                                'exclude' => true
                            ],
                            'metaProperty2' => [
                                'meta_property' => true,
                                'property_path' => 'meta_property_2',
                                'exclude' => true
                            ]
                        ]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenThereIsFieldsFilterForBothEntityTypeAndEntityClass(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'id' => null,
                'field1' => null,
                'field2' => null,
                'field3' => null
            ]
        ];

        $this->context->setExtras([
            new FilterFieldsConfigExtra([
                'primary_entity' => ['field1'],
                self::TEST_CLASS_NAME => ['field2']
            ])
        ]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, true)
            ->willReturn($rootEntityMetadata);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('primary_entity', DataType::ENTITY_CLASS, $requestType, false, false, [])
            ->willReturn(self::TEST_CLASS_NAME);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields' => [
                    'id' => null,
                    'field1' => null,
                    'field2' => null,
                    'field3' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenThereIsFieldsFilterForBothEntityTypeAndEntityClassAndFirstOneIsNull(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'id' => null,
                'field1' => null,
                'field2' => null,
                'field3' => null
            ]
        ];

        $this->context->setExtras([
            new FilterFieldsConfigExtra([
                'primary_entity' => null,
                self::TEST_CLASS_NAME => ['field2']
            ])
        ]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, true)
            ->willReturn($rootEntityMetadata);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('primary_entity', DataType::ENTITY_CLASS, $requestType, false, false, [])
            ->willReturn(self::TEST_CLASS_NAME);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields' => [
                    'id' => null,
                    'field1' => [
                        'exclude' => true
                    ],
                    'field2' => null,
                    'field3' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }


    public function testProcessWhenThereIsFieldsFilterForBothEntityTypeAndEntityClassAndSecondOneIsNull(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'id' => null,
                'field1' => null,
                'field2' => null,
                'field3' => null
            ]
        ];

        $this->context->setExtras([
            new FilterFieldsConfigExtra([
                'primary_entity' => ['field1'],
                self::TEST_CLASS_NAME => null
            ])
        ]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, true)
            ->willReturn($rootEntityMetadata);

        $requestType = $this->context->getRequestType();
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('primary_entity', DataType::ENTITY_CLASS, $requestType, false, false, [])
            ->willReturn(self::TEST_CLASS_NAME);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields' => [
                    'id' => null,
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'field3' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }
}
