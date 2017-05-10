<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared\CompleteDefinition;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition\CompleteAssociationHelper;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition\CompleteCustomAssociationHelper;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition\CompleteEntityDefinitionHelper;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ExpandedAssociationExtractor;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class CompleteEntityDefinitionHelperTest extends CompleteDefinitionHelperTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $customAssociationHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $exclusionProvider;

    /** @var CompleteEntityDefinitionHelper */
    protected $completeEntityDefinitionHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->customAssociationHelper = $this->createMock(CompleteCustomAssociationHelper::class);
        $this->exclusionProvider = $this->createMock(ExclusionProviderInterface::class);

        $this->completeEntityDefinitionHelper = new CompleteEntityDefinitionHelper(
            $this->doctrineHelper,
            new CompleteAssociationHelper($this->configProvider),
            $this->customAssociationHelper,
            $this->exclusionProvider,
            new ExpandedAssociationExtractor()
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
                ],
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->exclusionProvider->expects($this->exactly(6))
            ->method('isIgnoredField')
            ->willReturnMap(
                [
                    [$rootEntityMetadata, 'id', false],
                    [$rootEntityMetadata, 'field1', false],
                    [$rootEntityMetadata, 'field3', true],
                    [$rootEntityMetadata, 'field4', false],
                    [$rootEntityMetadata, 'realField6', false],
                    [$rootEntityMetadata, 'realField7', true],
                ]
            );

        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
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
                    'realField7',
                ]
            );
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
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
                    ],
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
                ],
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
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
                    ],
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
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
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
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
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
                    ],
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
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
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
                    ],
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
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'realAssociation1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'realAssociation1')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
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
                    ],
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
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->never())
            ->method('isIgnoredRelation');

        $this->configProvider->expects($this->once())
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
                    ],
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
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->never())
            ->method('isIgnoredRelation');

        $this->configProvider->expects($this->once())
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
                    ],
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
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(true);

        $this->configProvider->expects($this->once())
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
                    ],
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
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
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
                    ],
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
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
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
                    ],
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
                ],
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $context->setExtras([new FilterIdentifierFieldsConfigExtra()]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects($this->once())
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
                ],
            ]
        ]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $context->setExtras([new FilterIdentifierFieldsConfigExtra()]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects($this->once())
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
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects($this->once())
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
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['name']);

        $this->doctrineHelper->expects($this->once())
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

    public function testCompleteDefinitionForTableInheritanceEntity()
    {
        $config = $this->createConfigObject([]);
        $context = new ConfigContext();
        $context->setClassName(self::TEST_CLASS_NAME);
        $context->setVersion(self::TEST_VERSION);
        $context->getRequestType()->add(self::TEST_REQUEST_TYPE);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects($this->never())
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
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects($this->never())
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
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects($this->never())
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
        $rootEntityMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1' => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_MANY
                    ]
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);


        $this->configProvider->expects($this->once())
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
                    ],
                ]
            ],
            $config
        );
    }
}
