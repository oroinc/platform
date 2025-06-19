<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteSorters;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CompleteSortersTest extends ConfigProcessorTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private ConfigManager&MockObject $configManager;
    private CompleteSorters $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->processor = new CompleteSorters($this->doctrineHelper, $this->configManager);
    }

    public function testProcessForAlreadyCompletedSorters(): void
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

    public function testProcessForNotManageableEntity(): void
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

    public function testIdentifierField(): void
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testNotIndexedField(): void
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testIndexedField(): void
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testRenamedIndexedField(): void
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testExcludedIndexedField(): void
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testIndexedFieldWithExcludedSorterInConfig(): void
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testRenamedIndexedFieldAndRenamedSorter(): void
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testRenamedFieldAndRenamedSorterByIndexedField(): void
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testIndexedToOneAssociation(): void
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
            ->method('hasAssociation')
            ->with('toOneAssociation')
            ->willReturn(true);
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testIndexedToManyAssociation(): void
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
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('hasAssociation')
            ->with('toManyAssociation')
            ->willReturn(true);
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testExcludedIndexedToOneAssociation(): void
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
            ->method('hasAssociation')
            ->with('toOneAssociation')
            ->willReturn(true);
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testExcludedIndexedToManyAssociation(): void
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
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('hasAssociation')
            ->with('toManyAssociation')
            ->willReturn(true);
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testRenamedIndexedToOneAssociation(): void
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
            ->method('hasAssociation')
            ->with('realToOneAssociation')
            ->willReturn(true);
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testRenamedIndexedToManyAssociation(): void
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
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('hasAssociation')
            ->with('realToManyAssociation')
            ->willReturn(true);
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testRenamedAssociationAndRenamedSorterByIndexedToOneAssociation(): void
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
            ->method('hasAssociation');
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testRenamedAssociationAndRenamedSorterByIndexedToManyAssociation(): void
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
            ->method('hasAssociation');
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
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

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

    public function testEnumOptionEntity(): void
    {
        $entityClass = EnumOption::class;
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'       => null,
                'priority' => null
            ]
        ];

        $sorters = [];

        $rootEntityMetadata = $this->getClassMetadataMock($entityClass);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        $this->context->setClassName($entityClass);
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testEnumAssociation(): void
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'enumField' => [
                    'target_class' => 'Extend\Entity\EV_Test_Enum',
                    'target_type'  => 'to-one',
                    'fields'       => [
                        'id' => null
                    ]
                ]
            ]
        ];

        $sorters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('enumField')
            ->willReturn(false);
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
            ->willReturn([]);

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, null, true],
                [self::TEST_CLASS_NAME, 'enumField', true]
            ]);
        $this->configManager->expects(self::once())
            ->method('getIds')
            ->with('extend', self::TEST_CLASS_NAME, true)
            ->willReturn([
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'id', 'integer'),
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'notAccessibleEnumField', 'enum'),
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'enumField', 'enum')
            ]);
        $this->configManager->expects(self::exactly(3))
            ->method('getFieldConfig')
            ->willReturnMap([
                [
                    'extend',
                    self::TEST_CLASS_NAME,
                    'notAccessibleEnumField',
                    new Config(
                        new FieldConfigId('extend', self::TEST_CLASS_NAME, 'notAccessibleEnumField', 'enum'),
                        ['is_extend' => true, 'state' => ExtendScope::STATE_NEW]
                    )
                ],
                [
                    'extend',
                    self::TEST_CLASS_NAME,
                    'enumField',
                    new Config(
                        new FieldConfigId('extend', self::TEST_CLASS_NAME, 'enumField', 'enum'),
                        ['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]
                    )
                ],
                [
                    'enum',
                    self::TEST_CLASS_NAME,
                    'enumField',
                    new Config(
                        new FieldConfigId('enum', self::TEST_CLASS_NAME, 'enumField', 'enum'),
                        ['enum_code' => 'test_enum']
                    )
                ]
            ]);
        $this->configManager->expects(self::once())
            ->method('getId')
            ->with('extend', self::TEST_CLASS_NAME, 'enumField')
            ->willReturn(new FieldConfigId('extend', self::TEST_CLASS_NAME, 'enumField', 'enum'));

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'enumField' => null
                ]
            ],
            $this->context->getSorters()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testMultiEnumAssociation(): void
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'multiEnumField' => [
                    'target_class' => 'Extend\Entity\EV_Test_Enum',
                    'target_type'  => 'to-many',
                    'fields'       => [
                        'id' => null
                    ]
                ]
            ]
        ];

        $sorters = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('hasAssociation')
            ->with('multiEnumField')
            ->willReturn(false);
        $rootEntityMetadata->expects(self::never())
            ->method('isCollectionValuedAssociation');
        $rootEntityMetadata->expects(self::never())
            ->method('getAssociationTargetClass');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, true)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedFields')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn([]);
        $this->doctrineHelper->expects(self::once())
            ->method('getIndexedAssociations')
            ->with(self::identicalTo($rootEntityMetadata))
            ->willReturn(['toManyAssociation' => 'integer']);

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, null, true],
                [self::TEST_CLASS_NAME, 'multiEnumField', true]
            ]);
        $this->configManager->expects(self::once())
            ->method('getIds')
            ->with('extend', self::TEST_CLASS_NAME, true)
            ->willReturn([
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'id', 'integer'),
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'notAccessibleMultiEnumField', 'multiEnum'),
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'multiEnumField', 'multiEnum')
            ]);
        $this->configManager->expects(self::exactly(3))
            ->method('getFieldConfig')
            ->willReturnMap([
                [
                    'extend',
                    self::TEST_CLASS_NAME,
                    'notAccessibleMultiEnumField',
                    new Config(
                        new FieldConfigId('extend', self::TEST_CLASS_NAME, 'notAccessibleMultiEnumField', 'multiEnum'),
                        ['is_extend' => true, 'state' => ExtendScope::STATE_NEW]
                    )
                ],
                [
                    'extend',
                    self::TEST_CLASS_NAME,
                    'multiEnumField',
                    new Config(
                        new FieldConfigId('extend', self::TEST_CLASS_NAME, 'multiEnumField', 'multiEnum'),
                        ['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]
                    )
                ],
                [
                    'enum',
                    self::TEST_CLASS_NAME,
                    'multiEnumField',
                    new Config(
                        new FieldConfigId('enum', self::TEST_CLASS_NAME, 'multiEnumField', 'multiEnum'),
                        ['enum_code' => 'test_enum']
                    )
                ]
            ]);
        $this->configManager->expects(self::once())
            ->method('getId')
            ->with('extend', self::TEST_CLASS_NAME, 'multiEnumField')
            ->willReturn(new FieldConfigId('extend', self::TEST_CLASS_NAME, 'multiEnumField', 'multiEnum'));

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setSorters($this->createConfigObject($sorters, ConfigUtil::SORTERS));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'multiEnumField' => [
                        'property_path' => 'multiEnumField.id'
                    ]
                ]
            ],
            $this->context->getSorters()
        );
    }
}
