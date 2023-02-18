<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\CompleteDefinition;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\CompleteAssociationHelper;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\CompleteCustomDataTypeHelper;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\CompleteEntityDefinitionHelper;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\ExclusionProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\ExpandedAssociationExtractor;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityFieldFilteringHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class CompleteEntityDefinitionHelperTest extends CompleteDefinitionHelperTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityOverrideProviderInterface */
    private $entityOverrideProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CompleteCustomDataTypeHelper */
    private $customDataTypeHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExclusionProviderRegistry */
    private $exclusionProviderRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var CompleteEntityDefinitionHelper */
    private $completeEntityDefinitionHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->customDataTypeHelper = $this->createMock(CompleteCustomDataTypeHelper::class);
        $this->exclusionProviderRegistry = $this->createMock(ExclusionProviderRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $entityOverrideProviderRegistry = $this->createMock(EntityOverrideProviderRegistry::class);
        $entityOverrideProviderRegistry->expects(self::any())
            ->method('getEntityOverrideProvider')
            ->willReturn($this->entityOverrideProvider);

        $this->completeEntityDefinitionHelper = new CompleteEntityDefinitionHelper(
            $this->doctrineHelper,
            $entityOverrideProviderRegistry,
            new EntityIdHelper(),
            new CompleteAssociationHelper($this->configProvider),
            $this->customDataTypeHelper,
            $this->exclusionProviderRegistry,
            new ExpandedAssociationExtractor(),
            new EntityFieldFilteringHelper($this->configManager)
        );
    }

    private function getEntityConfig(array $config): ConfigInterface
    {
        return new Config(
            new EntityConfigId('extend', self::TEST_CLASS_NAME),
            $config
        );
    }

    private function getFieldConfig(string $fieldName, array $config): ConfigInterface
    {
        return new Config(
            new FieldConfigId('extend', self::TEST_CLASS_NAME, $fieldName, 'int'),
            $config
        );
    }

    public function testCompleteDefinitionForFields()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'field1' => null,
                'field2' => [
                    'exclude' => true
                ],
                'field5' => [
                    'exclude' => false
                ],
                'field6' => [
                    'property_path' => 'realField6'
                ],
                'field7' => [
                    'property_path' => 'realField7'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::exactly(6))
            ->method('isIgnoredField')
            ->willReturnMap([
                [$rootEntityMetadata, 'id', false],
                [$rootEntityMetadata, 'field1', false],
                [$rootEntityMetadata, 'field3', true],
                [$rootEntityMetadata, 'field4', false],
                [$rootEntityMetadata, 'realField6', false],
                [$rootEntityMetadata, 'realField7', true]
            ]);

        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(
                [
                    'id',
                    'field1',
                    'field2',
                    'field3',
                    'field4',
                    'field5',
                    'realField6',
                    'realField7'
                ]
            );
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'     => null,
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'field3' => [
                        'exclude' => true
                    ],
                    'field4' => null,
                    'field5' => null,
                    'field6' => [
                        'property_path' => 'realField6'
                    ],
                    'field7' => [
                        'exclude'       => true,
                        'property_path' => 'realField7'
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForFieldReplacedWithComputedField()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'field1' => [
                    'data_type'     => 'integer',
                    'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'field1']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredField')
            ->with(self::identicalTo($rootEntityMetadata), 'id')
            ->willReturn(false);
        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'     => null,
                    'field1' => [
                        'data_type'     => 'integer',
                        'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForCompletedAssociation()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'exclusion_policy' => 'all'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'identifier_field_names' => ['id']
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForAssociationWithoutConfig()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn($this->createRelationConfigObject());

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => null
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForAssociation()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForNewAssociation()
    {
        $config = $this->createConfigObject([]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForRenamedAssociation()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'property_path' => 'realAssociation1'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'realAssociation1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'realAssociation1')
            ->willReturn(false);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'property_path'          => 'realAssociation1',
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForExcludedAssociation()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'exclude' => true
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::never())
            ->method('isIgnoredRelation');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclude'                => true,
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForNotExcludedAssociation()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'exclude' => false
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::never())
            ->method('isIgnoredRelation');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForIgnoredAssociation()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(true);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclude'                => true,
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForAssociationReplacedWithComputedField()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'data_type'     => 'object',
                    'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::never())
            ->method('isIgnoredRelation');
        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'data_type'     => 'object',
                        'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForAssociationWithDataType()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'data_type' => 'string'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'data_type'              => 'string',
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForAssociationWithCompositeId()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id1', 'id2'],
                        'fields'                 => [
                            'id1' => [
                                'data_type' => 'integer'
                            ],
                            'id2' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id1', 'id2'],
                        'fields'                 => [
                            'id1' => [
                                'data_type' => 'integer'
                            ],
                            'id2' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForAssociationDoesNotExistInEntityAndConfiguredByTargetClassAndTargetType()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target',
                    'target_type'  => 'to-one'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(false);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForAssociationWithTargetClassAndTargetTypeAndDataType()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'data_type'    => 'some_custom_association',
                    'target_class' => 'Test\Association1Target',
                    'target_type'  => 'to-one'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);
        $rootEntityMetadata->expects(self::never())
            ->method('hasAssociation');

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'data_type'    => 'some_custom_association',
                        'target_class' => 'Test\Association1Target',
                        'target_type'  => 'to-one'
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForAssociationToOverriddenEntity()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->entityOverrideProvider->expects(self::once())
            ->method('getSubstituteEntityClass')
            ->with('Test\Association1Target')
            ->willReturn('Test\Association1SubstituteTarget');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1SubstituteTarget', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1SubstituteTarget',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForIdentifierFieldsOnly()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'id'     => null,
                'field1' => null,
                'field2' => [
                    'exclude' => true
                ],
                'field3' => [
                    'property_path' => 'realField3'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $context->setExtras([new FilterIdentifierFieldsConfigExtra()]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => null
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForIdentifierFieldsOnlyAndHasConfiguredIdFields()
    {
        $config = $this->createConfigObject([
            'identifier_field_names' => ['field1'],
            'fields'                 => [
                'id'     => null,
                'field1' => null,
                'field2' => [
                    'exclude' => true
                ],
                'field3' => [
                    'property_path' => 'realField3'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $context->setExtras([new FilterIdentifierFieldsConfigExtra()]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::never())
            ->method('getIdentifierFieldNames');

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['field1'],
                'fields'                 => [
                    'field1' => null
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForIdentifierFieldsOnlyWithIgnoredPropertyPath()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'id'     => null,
                'field1' => [
                    'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                ],
                'field2' => [
                    'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $context->setExtras([new FilterIdentifierFieldsConfigExtra()]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => null
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForIdentifierFieldsOnlyWithReplacedField()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'id'      => null,
                'field1'  => [
                    'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH
                ],
                '_field1' => [
                    'property_path' => 'field1'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $context->setExtras([new FilterIdentifierFieldsConfigExtra()]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => null
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForIdentifierFieldsOnlyWhenNoIdFieldInConfig()
    {
        $config = $this->createConfigObject([
            'fields' => []
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $context->setExtras([new FilterIdentifierFieldsConfigExtra()]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => null
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForIdentifierFieldsOnlyWhenNoIdFieldInConfigAndHasConfiguredIdFields()
    {
        $config = $this->createConfigObject([
            'identifier_field_names' => ['field1'],
            'fields'                 => []
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $context->setExtras([new FilterIdentifierFieldsConfigExtra()]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::never())
            ->method('getIdentifierFieldNames');

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['field1'],
                'fields'                 => [
                    'field1' => null
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForIdentifierFieldsOnlyWithRenamedIdFieldInConfig()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'renamedId' => [
                    'property_path' => 'name'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $context->setExtras([new FilterIdentifierFieldsConfigExtra()]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['name']);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['renamedId'],
                'fields'                 => [
                    'renamedId' => [
                        'property_path' => 'name'
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForIdentifierFieldsOnlyWithRenamedIdFieldInConfigAndHasConfiguredIdFields()
    {
        $config = $this->createConfigObject([
            'identifier_field_names' => ['field1'],
            'fields'                 => [
                'field1' => [
                    'property_path' => 'realField1'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $context->setExtras([new FilterIdentifierFieldsConfigExtra()]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::never())
            ->method('getIdentifierFieldNames');

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['field1'],
                'fields'                 => [
                    'field1' => [
                        'property_path' => 'realField1'
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForIdentifierFieldsOnlyWhenIdFieldIsExcluded()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The identifier field "id" for "Test\Class" entity must not be excluded.');

        $config = $this->createConfigObject([
            'fields' => [
                'id'    => [
                    'exclude' => true
                ],
                'field' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $context->setExtras([new FilterIdentifierFieldsConfigExtra()]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);
    }

    public function testCompleteDefinitionForTableInheritanceEntity()
    {
        $config = $this->createConfigObject([]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'        => null,
                    '__class__' => [
                        'meta_property' => true,
                        'data_type'     => 'string'
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForTableInheritanceEntityWhenClassNameFieldAlreadyExists()
    {
        $config = $this->createConfigObject([
            'fields' => [
                '__class__' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'        => null,
                    '__class__' => null
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForTableInheritanceEntityWhenClassNameFieldAlreadyExistsAndRenamed()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'type' => [
                    'property_path' => '__class__'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'   => null,
                    'type' => [
                        'property_path' => '__class__'
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteDefinitionForTableInheritanceAssociation()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_MANY
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id'        => null,
                            '__class__' => null
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-many',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id'        => null,
                            '__class__' => null
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    /**
     * @dataProvider completeCustomIdentifierDataProvider
     */
    public function testCompleteCustomIdentifier(
        string $targetAction,
        bool $usesIdGenerator,
        array $config,
        array $expectedConfig
    ) {
        $config = $this->createConfigObject($config);
        $context = new ConfigContext();
        $context->setTargetAction($targetAction);
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('usesIdGenerator')
            ->willReturn($usesIdGenerator);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'field']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig($expectedConfig, $config);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function completeCustomIdentifierDataProvider(): array
    {
        return [
            'CREATE action, id generator, configured custom id'              => [
                'targetAction'    => ApiAction::CREATE,
                'usesIdGenerator' => true,
                'config'          => [
                    'identifier_field_names' => ['field'],
                    'fields'                 => []
                ],
                'expectedConfig'  => [
                    'identifier_field_names' => ['field'],
                    'fields'                 => [
                        'id'    => [
                            'form_options' => [
                                'mapped' => false
                            ]
                        ],
                        'field' => null
                    ]
                ]
            ],
            'UPDATE action, id generator, configured custom id'              => [
                'targetAction'    => ApiAction::UPDATE,
                'usesIdGenerator' => true,
                'config'          => [
                    'identifier_field_names' => ['field'],
                    'fields'                 => []
                ],
                'expectedConfig'  => [
                    'identifier_field_names' => ['field'],
                    'fields'                 => [
                        'id'    => [
                            'form_options' => [
                                'mapped' => false
                            ]
                        ],
                        'field' => null
                    ]
                ]
            ],
            'another action, id generator, configured custom id'             => [
                'targetAction'    => ApiAction::GET,
                'usesIdGenerator' => true,
                'config'          => [
                    'identifier_field_names' => ['field'],
                    'fields'                 => []
                ],
                'expectedConfig'  => [
                    'identifier_field_names' => ['field'],
                    'fields'                 => [
                        'id'    => null,
                        'field' => null
                    ]
                ]
            ],
            'no id generator, configured custom id'                          => [
                'targetAction'    => ApiAction::CREATE,
                'usesIdGenerator' => false,
                'config'          => [
                    'identifier_field_names' => ['field'],
                    'fields'                 => []
                ],
                'expectedConfig'  => [
                    'identifier_field_names' => ['field'],
                    'fields'                 => [
                        'id'    => null,
                        'field' => null
                    ]
                ]
            ],
            'id generator, configured custom id equals to entity id'         => [
                'targetAction'    => ApiAction::CREATE,
                'usesIdGenerator' => true,
                'config'          => [
                    'identifier_field_names' => ['id'],
                    'fields'                 => []
                ],
                'expectedConfig'  => [
                    'identifier_field_names' => ['id'],
                    'fields'                 => [
                        'id'    => null,
                        'field' => null
                    ]
                ]
            ],
            'id generator, configured custom id equals to renamed entity id' => [
                'targetAction'    => ApiAction::CREATE,
                'usesIdGenerator' => true,
                'config'          => [
                    'identifier_field_names' => ['renamedId'],
                    'fields'                 => [
                        'renamedId' => [
                            'property_path' => 'id'
                        ]
                    ]
                ],
                'expectedConfig'  => [
                    'identifier_field_names' => ['renamedId'],
                    'fields'                 => [
                        'renamedId' => [
                            'property_path' => 'id'
                        ],
                        'field'     => null
                    ]
                ]
            ]
        ];
    }

    public function testCompleteCustomIdentifierWhenIdFieldIsExcluded()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The identifier field "field" for "Test\Class" entity must not be excluded.');

        $config = $this->createConfigObject([
            'identifier_field_names' => ['field'],
            'fields'                 => [
                'id'    => null,
                'field' => [
                    'exclude' => true
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setTargetAction(ApiAction::GET);
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('usesIdGenerator')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'field']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);
    }

    public function testCompleteCustomIdentifierWhenIdFieldIsNotDefined()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The identifier field "unknownField" for "Test\Class" entity is not defined.');

        $config = $this->createConfigObject([
            'identifier_field_names' => ['unknownField'],
            'fields'                 => [
                'id' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setTargetAction(ApiAction::GET);
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('usesIdGenerator')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'field']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);
    }

    public function testShouldThrowCorrectExceptionWhenDependsOnNameOfUndefinedPropertyAndGetConfigThrowException()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'field' => [
                    'depends_on' => ['undefinedField']
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(self::TEST_CLASS_NAME, $context->getVersion(), $context->getRequestType())
            ->willThrowException(new \RuntimeException('circular dependency detected'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot resolve dependency to "undefinedField" specified for "Test\Class::field".'
            . ' Check "depends_on" option for this field.'
        );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);
    }

    public function testShouldThrowCorrectExceptionWhenDependsOnNotFullyConfiguredFieldReplacement()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'field' => [
                    'property_path' => ConfigUtil::IGNORE_PROPERTY_PATH,
                    'depends_on'    => ['field']
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(self::TEST_CLASS_NAME, $context->getVersion(), $context->getRequestType())
            ->willThrowException(new \RuntimeException('circular dependency detected'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot resolve dependency to "field" specified for "Test\Class::field".'
            . ' Check "depends_on" option for this field.'
            . ' If the value of this option is correct you can declare an excluded field with "field" property path.'
            . ' For example:' . "\n"
            . '_field:' . "\n"
            . '    property_path: field' . "\n"
            . '    exclude: true'
        );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);
    }

    public function testShouldThrowCorrectExceptionWhenPropertyPathPointsToFieldItself()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association' => [
                    'property_path' => 'association.name'
                ]
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(self::TEST_CLASS_NAME, $context->getVersion(), $context->getRequestType())
            ->willThrowException(new \RuntimeException('circular dependency detected'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot resolve property path "association.name" specified for "Test\Class::association".'
            . ' Check "property_path" option for this field.'
            . ' If it is correct you can rename the target property as a possible solution.'
            . ' For example:' . "\n"
            . '_association:' . "\n"
            . '    property_path: association'
        );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);
    }

    public function testCustomFieldsExclusionPolicyForFieldsOfNonConfigurableEntity()
    {
        $config = $this->createConfigObject([
            'exclusion_policy' => 'custom_fields',
            'fields'           => [
                'field1' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::exactly(3))
            ->method('isIgnoredField')
            ->willReturnMap([
                [$rootEntityMetadata, 'id', false],
                [$rootEntityMetadata, 'field1', false],
                [$rootEntityMetadata, 'field2', false]
            ]);

        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([
                'id',
                'field1',
                'field2'
            ]);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'custom_fields',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'     => null,
                    'field1' => null,
                    'field2' => null
                ]
            ],
            $config
        );
    }

    public function testCustomFieldsExclusionPolicyForFieldsOfCustomEntity()
    {
        $config = $this->createConfigObject([
            'exclusion_policy' => 'custom_fields',
            'fields'           => [
                'field1' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn(
                $this->getEntityConfig(['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM])
            );
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::exactly(3))
            ->method('isIgnoredField')
            ->willReturnMap([
                [$rootEntityMetadata, 'id', false],
                [$rootEntityMetadata, 'field1', false],
                [$rootEntityMetadata, 'field2', false]
            ]);

        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([
                'id',
                'field1',
                'field2'
            ]);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'custom_fields',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'     => null,
                    'field1' => null,
                    'field2' => null
                ]
            ],
            $config
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCustomFieldsExclusionPolicyForFieldsOfExtendSystemEntity()
    {
        $config = $this->createConfigObject([
            'exclusion_policy' => 'custom_fields',
            'fields'           => [
                'field1' => null,
                'field3' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configManager->expects(self::exactly(4))
            ->method('hasConfig')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, null, true],
                [self::TEST_CLASS_NAME, 'id', true],
                [self::TEST_CLASS_NAME, 'field2', true],
                [self::TEST_CLASS_NAME, 'field4', true]
            ]);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn(
                $this->getEntityConfig(['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
            );
        $this->configManager->expects(self::exactly(3))
            ->method('getFieldConfig')
            ->willReturnMap([
                [
                    'extend',
                    self::TEST_CLASS_NAME,
                    'id',
                    $this->getFieldConfig('id', ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
                ],
                [
                    'extend',
                    self::TEST_CLASS_NAME,
                    'field2',
                    $this->getFieldConfig('field2', ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
                ],
                [
                    'extend',
                    self::TEST_CLASS_NAME,
                    'field4',
                    $this->getFieldConfig('field4', ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM])
                ]
            ]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::exactly(4))
            ->method('isIgnoredField')
            ->willReturnMap([
                [$rootEntityMetadata, 'id', false],
                [$rootEntityMetadata, 'field1', false],
                [$rootEntityMetadata, 'field2', false],
                [$rootEntityMetadata, 'field3', false]
            ]);

        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([
                'id',
                'field1',
                'field2',
                'field3',
                'field4'
            ]);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'custom_fields',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'     => null,
                    'field1' => null,
                    'field2' => null,
                    'field3' => null
                ]
            ],
            $config
        );
    }

    public function testCustomFieldsExclusionPolicyForAssociationOfNonConfigurableEntity()
    {
        $config = $this->createConfigObject([
            'exclusion_policy' => 'custom_fields',
            'fields'           => [
                'association1' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'custom_fields',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCustomFieldsExclusionPolicyForAssociationOfCustomEntity()
    {
        $config = $this->createConfigObject([
            'exclusion_policy' => 'custom_fields',
            'fields'           => [
                'association1' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn(
                $this->getEntityConfig(['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM])
            );
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'custom_fields',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCustomFieldsExclusionPolicyForConfiguredExplicitlyAssociationOfExtendSystemEntity()
    {
        $config = $this->createConfigObject([
            'exclusion_policy' => 'custom_fields',
            'fields'           => [
                'association1' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, null, true],
                [self::TEST_CLASS_NAME, 'id', true]
            ]);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn(
                $this->getEntityConfig(['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
            );
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('extend', self::TEST_CLASS_NAME, 'id')
            ->willReturn(
                $this->getFieldConfig('id', ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
            );

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'custom_fields',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCustomFieldsExclusionPolicyForNotConfiguredExplicitlySystemAssociationOfExtendSystemEntity()
    {
        $config = $this->createConfigObject([
            'exclusion_policy' => 'custom_fields',
            'fields'           => []
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configManager->expects(self::exactly(3))
            ->method('hasConfig')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, null, true],
                [self::TEST_CLASS_NAME, 'id', true],
                [self::TEST_CLASS_NAME, 'association1', true]
            ]);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn(
                $this->getEntityConfig(['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
            );
        $this->configManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                [
                    'extend',
                    self::TEST_CLASS_NAME,
                    'id',
                    $this->getFieldConfig('id', ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
                ],
                [
                    'extend',
                    self::TEST_CLASS_NAME,
                    'association1',
                    $this->getFieldConfig('association1', ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
                ]
            ]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Association1Target', $context->getVersion(), $context->getRequestType())
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'custom_fields',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCustomFieldsExclusionPolicyForNotConfiguredExplicitlyCustomAssociationOfExtendSystemEntity()
    {
        $config = $this->createConfigObject([
            'exclusion_policy' => 'custom_fields',
            'fields'           => []
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configManager->expects(self::exactly(3))
            ->method('hasConfig')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, null, true],
                [self::TEST_CLASS_NAME, 'id', true],
                [self::TEST_CLASS_NAME, 'association1', true]
            ]);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn(
                $this->getEntityConfig(['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
            );
        $this->configManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                [
                    'extend',
                    self::TEST_CLASS_NAME,
                    'id',
                    $this->getFieldConfig('id', ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM])
                ],
                [
                    'extend',
                    self::TEST_CLASS_NAME,
                    'association1',
                    $this->getFieldConfig('association1', ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM])
                ]
            ]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::never())
            ->method('isIgnoredRelation');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'custom_fields',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => null
                ]
            ],
            $config
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCompleteDefinitionForDependsOnAssociationOfNonConfigurableEntity(): void
    {
        $config = $this->createConfigObject([
            'fields' => [
                'id' => [
                    'depends_on' => ['association1.id']
                ],
                'association1' => null
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $this->configManager->expects(self::never())
            ->method('getEntityConfig');
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );
        $associationMetadata = new ClassMetadata('Test\Association1Target');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $associationMetadata]
            ]);

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::exactly(2))
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);
        // field association1 is collapse and the first time call of getConfig will get only identifier field.
        $this->configProvider->expects(self::exactly(2))
            ->method('getConfig')
            ->withConsecutive(
                [
                    'Test\Association1Target',
                    $context->getVersion(),
                    $context->getRequestType(),
                    [new FilterIdentifierFieldsConfigExtra(), new EntityDefinitionConfigExtra()]
                ],
                [
                    'Test\Association1Target',
                    $context->getVersion(),
                    $context->getRequestType(),
                    [new EntityDefinitionConfigExtra()],
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'property_path' => 'customizedIdentifier'
                            ]
                        ]
                    ]
                ),
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'property_path' => 'customizedIdentifier'
                            ],
                            'renameId' => [
                                'property_path' => 'id'
                            ]
                        ]
                    ]
                )
            );

        $this->completeEntityDefinitionHelper->completeDefinition($config, $context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => [
                        'depends_on' => ['association1.id']
                    ],
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'target_class'           => 'Test\Association1Target',
                        'target_type'            => 'to-one',
                        'collapse'               => true,
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'property_path' => 'customizedIdentifier'
                            ],
                            'renameId' => [
                                'property_path' => 'id',
                                'exclude' => true
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }
}
