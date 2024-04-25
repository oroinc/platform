<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteSorters;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CompleteSortersTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var CompleteSorters */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new CompleteSorters($this->doctrineHelper);
    }

    public function testProcessForAlreadyCompletedSorters()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclude' => true
                ],
                'field3' => null,
                'field4' => null
            ]
        ];

        $sorters = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => null,
                'field3' => [
                    'exclude' => true
                ]
            ]
        ];

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'field3' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];

        $sorters = [];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getSorters()
        );
    }

    public function testIdentifierField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id' => null
            ]
        ];
        $sorters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id' => null
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testNotIndexedField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'field1' => null
            ]
        ];

        $sorters = [
            'fields' => [
                'field1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
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

    public function testIndexedField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'field1' => null
            ]
        ];

        $sorters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['field1' => 'integer']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
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

    public function testRenamedIndexedField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'field1' => [
                    'property_path' => 'realField1'
                ]
            ]
        ];

        $sorters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['realField1' => 'integer']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
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

    public function testExcludedIndexedField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'field1' => [
                    'exclude' => true
                ]
            ]
        ];

        $sorters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['field1' => 'integer']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testIndexedFieldWithExcludedSorterInConfig()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'field1' => null
            ]
        ];

        $sorters = [
            'fields' => [
                'field1' => [
                    'exclude' => true
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['field1' => 'integer']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testRenamedIndexedFieldAndRenamedSorter()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'field1' => [
                    'property_path' => 'realField1'
                ]
            ]
        ];

        $sorters = [
            'fields' => [
                'field1' => [
                    'property_path' => 'realSorterField1'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['realField1' => 'integer']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'property_path' => 'realSorterField1'
                    ]
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testRenamedFieldAndRenamedSorterByIndexedField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'field1' => [
                    'property_path' => 'realField1'
                ]
            ]
        ];

        $sorters = [
            'fields' => [
                'field1' => [
                    'property_path' => 'realSorterField1'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['realSorterField1' => 'integer']);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'property_path' => 'realSorterField1'
                    ]
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testIndexedToOneAssociation()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'toOneAssociation' => null
            ]
        ];

        $sorters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('toOneAssociation')
            ->willReturn(false);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['toOneAssociation' => 'integer']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'toOneAssociation' => null
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testIndexedToManyAssociation()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'toManyAssociation' => null
            ]
        ];

        $sorters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $toManyAssociationTargetEntityClass = 'Test\ToManyTarget';
        $rootEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('toManyAssociation')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('toManyAssociation')
            ->willReturn($toManyAssociationTargetEntityClass);
        $toManyAssociationTargetEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $toManyAssociationTargetEntityMetadata->expects(self::once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                [$toManyAssociationTargetEntityClass, true, $toManyAssociationTargetEntityMetadata]
            ]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['toManyAssociation' => 'integer']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'toManyAssociation' => [
                        'property_path' => 'toManyAssociation.id'
                    ]
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testExcludedIndexedToOneAssociation()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'toOneAssociation' => [
                    'exclude' => true
                ]
            ]
        ];

        $sorters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('toOneAssociation')
            ->willReturn(false);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['toOneAssociation' => 'integer']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'toOneAssociation' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testExcludedIndexedToManyAssociation()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'toManyAssociation' => [
                    'exclude' => true
                ]
            ]
        ];

        $sorters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $toManyAssociationTargetEntityClass = 'Test\ToManyTarget';
        $rootEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('toManyAssociation')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('toManyAssociation')
            ->willReturn($toManyAssociationTargetEntityClass);
        $toManyAssociationTargetEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $toManyAssociationTargetEntityMetadata->expects(self::once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                [$toManyAssociationTargetEntityClass, true, $toManyAssociationTargetEntityMetadata]
            ]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['toManyAssociation' => 'integer']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'toManyAssociation' => [
                        'exclude'       => true,
                        'property_path' => 'toManyAssociation.id'
                    ]
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testRenamedIndexedToOneAssociation()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'toOneAssociation' => [
                    'property_path' => 'realToOneAssociation'
                ]
            ]
        ];

        $sorters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('realToOneAssociation')
            ->willReturn(false);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['realToOneAssociation' => 'integer']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'toOneAssociation' => null
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testRenamedIndexedToManyAssociation()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'toManyAssociation' => [
                    'property_path' => 'realToManyAssociation'
                ]
            ]
        ];

        $sorters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $toManyAssociationTargetEntityClass = 'Test\ToManyTarget';
        $rootEntityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('realToManyAssociation')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('realToManyAssociation')
            ->willReturn($toManyAssociationTargetEntityClass);
        $toManyAssociationTargetEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $toManyAssociationTargetEntityMetadata->expects(self::once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                [$toManyAssociationTargetEntityClass, true, $toManyAssociationTargetEntityMetadata]
            ]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['realToManyAssociation' => 'integer']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'toManyAssociation' => [
                        'property_path' => 'realToManyAssociation.id'
                    ]
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testRenamedAssociationAndRenamedSorterByIndexedToOneAssociation()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'toOneAssociation' => [
                    'property_path' => 'realToOneAssociation'
                ]
            ]
        ];

        $sorters = [
            'fields' => [
                'toOneAssociation' => [
                    'property_path' => 'realSorterToOneAssociation'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::never())
            ->method('isCollectionValuedAssociation');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['realSorterToOneAssociation' => 'integer']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'toOneAssociation' => [
                        'property_path' => 'realSorterToOneAssociation'
                    ]
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testRenamedAssociationAndRenamedSorterByIndexedToManyAssociation()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'toManyAssociation' => [
                    'property_path' => 'realToManyAssociation'
                ]
            ]
        ];

        $sorters = [
            'fields' => [
                'toManyAssociation' => [
                    'property_path' => 'realSorterToManyAssociation.id'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::never())
            ->method('isCollectionValuedAssociation');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['realSorterToManyAssociation' => 'integer']);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'toManyAssociation' => [
                        'property_path' => 'realSorterToManyAssociation.id'
                    ]
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testEnumEntity()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'       => null,
                'priority' => null
            ]
        ];

        $sorters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(TestEnumValue::class);
        $rootEntityMetadata->expects(self::once())
            ->method('hasField')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getTypeOfField')
            ->willReturn('integer');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(TestEnumValue::class)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(TestEnumValue::class)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);

        $this->context->setClassName(TestEnumValue::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'       => null,
                    'priority' => null
                ]
            ],
            $this->context->getSorters()
        );
    }
}
