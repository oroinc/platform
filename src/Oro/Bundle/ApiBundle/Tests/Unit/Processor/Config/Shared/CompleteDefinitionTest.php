<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class CompleteDefinitionTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $exclusionProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var CompleteDefinition */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->exclusionProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface');
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new CompleteDefinition(
            $this->doctrineHelper,
            $this->exclusionProvider,
            $this->configProvider
        );
    }

    public function testProcessForAlreadyProcessedConfig()
    {
        $config = [
            'exclusion_policy' => 'all'
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

    public function testProcessFieldForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'field1' => null
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessCompletedAssociationForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'target_class'     => 'Test\Association1Target',
                    'exclusion_policy' => 'all'
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'target_class'     => 'Test\Association1Target',
                        'exclusion_policy' => 'all'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessAssociationWithoutConfigForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target'
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn($this->createRelationConfigObject());

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'target_class' => 'Test\Association1Target'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessAssociationForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target'
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
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

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'target_class'           => 'Test\Association1Target',
                        'exclusion_policy'       => 'all',
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
            $this->context->getResult()
        );
    }

    public function testProcessAssociationWithDataTypeForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target',
                    'data_type'    => 'string'
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
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

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'target_class'           => 'Test\Association1Target',
                        'data_type'              => 'string',
                        'exclusion_policy'       => 'all',
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
            $this->context->getResult()
        );
    }

    public function testProcessAssociationWithCompositeIdForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target',
                    'data_type'    => 'string'
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
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

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'target_class'           => 'Test\Association1Target',
                        'data_type'              => 'string',
                        'exclusion_policy'       => 'all',
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
            $this->context->getResult()
        );
    }

    /**
     * @param array|null $definition
     *
     * @return Config
     */
    protected function createRelationConfigObject(array $definition = null)
    {
        $config = new Config();
        if (null !== $definition) {
            $config->setDefinition($this->createConfigObject($definition));
        }

        return $config;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessFieldsForManageableEntity()
    {
        $config = [
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
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->exclusionProvider->expects($this->exactly(5))
            ->method('isIgnoredField')
            ->willReturnMap(
                [
                    [$rootEntityMetadata, 'field1', false],
                    [$rootEntityMetadata, 'field3', true],
                    [$rootEntityMetadata, 'field4', false],
                    [$rootEntityMetadata, 'realField6', false],
                    [$rootEntityMetadata, 'realField7', true],
                ]
            );

        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(
                [
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
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
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
            $this->context->getResult()
        );
    }

    public function testProcessCompletedAssociationForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'exclusion_policy' => 'all'
                ],
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn([]);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->once())
            ->method('isIgnoredRelation')
            ->with($rootEntityMetadata, 'association1')
            ->willReturn(false);

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclusion_policy' => 'all'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessAssociationWithoutConfigForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn([]);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
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
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn($this->createRelationConfigObject());

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessAssociationForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn([]);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
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
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
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

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclusion_policy'       => 'all',
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
            $this->context->getResult()
        );
    }

    public function testProcessNewAssociationForManageableEntity()
    {
        $config = [];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn([]);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
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
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
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

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclusion_policy'       => 'all',
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
            $this->context->getResult()
        );
    }

    public function testProcessRenamedAssociationForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'property_path' => 'realAssociation1'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn([]);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['realAssociation1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
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
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
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

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'property_path'          => 'realAssociation1',
                        'exclusion_policy'       => 'all',
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
            $this->context->getResult()
        );
    }

    public function testProcessExcludedAssociationForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'exclude' => true
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn([]);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->never())
            ->method('isIgnoredRelation');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
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

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclude'                => true,
                        'exclusion_policy'       => 'all',
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
            $this->context->getResult()
        );
    }

    public function testProcessNotExcludedAssociationForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'exclude' => false
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn([]);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->exclusionProvider->expects($this->never())
            ->method('isIgnoredRelation');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
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

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclusion_policy'       => 'all',
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
            $this->context->getResult()
        );
    }

    public function testProcessIgnoredAssociationForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn([]);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
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
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
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

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclude'                => true,
                        'exclusion_policy'       => 'all',
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
            $this->context->getResult()
        );
    }

    public function testProcessAssociationWithDataTypeForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type' => 'string'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn([]);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
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
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
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

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'data_type'              => 'string',
                        'exclusion_policy'       => 'all',
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
            $this->context->getResult()
        );
    }

    public function testProcessAssociationWithCompositeIdForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn([]);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(['association1' => ['targetEntity' => 'Test\Association1Target']]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
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
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
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

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclusion_policy'       => 'all',
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
            $this->context->getResult()
        );
    }

    public function testProcessIdentifierFieldsOnlyForManageableEntity()
    {
        $config = [
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
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->context->setExtras([new FilterIdentifierFieldsConfigExtra()]);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'id' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessIdentifierFieldsOnlyWhenNoIdFieldInConfigForManageableEntity()
    {
        $config = [
            'fields' => []
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->context->setExtras([new FilterIdentifierFieldsConfigExtra()]);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'id' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessIdentifierFieldsOnlyWithRenamedIdFieldInConfigForManageableEntity()
    {
        $config = [
            'fields' => [
                'renamedId' => [
                    'property_path' => 'name'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['name']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->context->setExtras([new FilterIdentifierFieldsConfigExtra()]);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'renamedId' => [
                        'property_path' => 'name'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessIdentifierFieldsOnlyForNotManageableEntity()
    {
        $config = [
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'     => null,
                'field1' => null,
                'field2' => [
                    'exclude' => true
                ],
                'field3' => [
                    'property_path' => 'realField3'
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setExtras([new FilterIdentifierFieldsConfigExtra()]);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessIdentifierFieldsOnlyWithRenamedIdFieldInConfigForNotManageableEntity()
    {
        $config = [
            'identifier_field_names' => ['renamedId'],
            'fields'                 => [
                'renamedId' => [
                    'property_path' => 'name'
                ]
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setExtras([new FilterIdentifierFieldsConfigExtra()]);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['renamedId'],
                'fields'                 => [
                    'renamedId' => [
                        'property_path' => 'name'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }
}
