<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\FilterFieldsByExtra;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;

class FilterFieldsByExtraTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var FilterFieldsByExtra */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new FilterFieldsByExtra(
            $this->doctrineHelper,
            $this->valueNormalizer
        );
    }

    public function testProcessForNotCompletedConfig()
    {
        $config = [
            'fields' => [
                'field1' => null
            ]
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoFields()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
            ]
        ];

        $this->doctrineHelper->expects($this->never())
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForNotManageableEntity()
    {
        $config = [
            'identifier_field_names' => ['id'],
            'exclusion_policy'       => 'all',
            'fields'                 => [
                'id'           => null,
                'field1'       => null,
                'field2'       => null,
                'association1' => [
                    'identifier_field_names' => ['id'],
                    'target_class'           => 'Test\Association1Target',
                    'exclusion_policy'       => 'all',
                    'fields'                 => [
                        'id'     => null,
                        'field1' => null,
                        'field2' => null,
                    ]
                ],
                'association2' => [
                    'identifier_field_names' => ['id'],
                    'target_class'           => 'Test\Association2Target',
                    'exclusion_policy'       => 'all',
                    'property_path'          => 'realAssociation2',
                    'fields'                 => [
                        'id'     => null,
                        'field1' => null,
                        'field2' => null,
                    ]
                ],
                'association3' => [
                    'identifier_field_names' => ['id'],
                    'target_class'           => 'Test\Association3Target',
                    'exclusion_policy'       => 'all',
                    'fields'                 => [
                        'id'     => null,
                        'field1' => null,
                    ]
                ],
            ]
        ];

        $this->context->setExtras(
            [
                new FilterFieldsConfigExtra(
                    [
                        'primary_entity'       => ['field1', 'association1', 'association2', 'association3'],
                        'association_1_entity' => ['id', 'field1'],
                        'association_2_entity' => ['field2'],
                    ]
                )
            ]
        );

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->valueNormalizer->expects($this->exactly(3))
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    [
                        'primary_entity',
                        DataType::ENTITY_CLASS,
                        $this->context->getRequestType(),
                        false,
                        self::TEST_CLASS_NAME
                    ],
                    [
                        'association_1_entity',
                        DataType::ENTITY_CLASS,
                        $this->context->getRequestType(),
                        false,
                        'Test\Association1Target'
                    ],
                    [
                        'association_2_entity',
                        DataType::ENTITY_CLASS,
                        $this->context->getRequestType(),
                        false,
                        'Test\Association2Target'
                    ],
                ]
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'exclusion_policy'       => 'all',
                'fields'                 => [
                    'id'           => null,
                    'field1'       => null,
                    'field2'       => [
                        'exclude' => true
                    ],
                    'association1' => [
                        'identifier_field_names' => ['id'],
                        'target_class'           => 'Test\Association1Target',
                        'exclusion_policy'       => 'all',
                        'fields'                 => [
                            'id'     => null,
                            'field1' => null,
                            'field2' => [
                                'exclude' => true
                            ],
                        ]
                    ],
                    'association2' => [
                        'identifier_field_names' => ['id'],
                        'target_class'           => 'Test\Association2Target',
                        'exclusion_policy'       => 'all',
                        'property_path'          => 'realAssociation2',
                        'fields'                 => [
                            'id'     => null,
                            'field1' => [
                                'exclude' => true
                            ],
                            'field2' => null,
                        ]
                    ],
                    'association3' => [
                        'identifier_field_names' => ['id'],
                        'target_class'           => 'Test\Association3Target',
                        'exclusion_policy'       => 'all',
                        'fields'                 => [
                            'id'     => null,
                            'field1' => null,
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'           => null,
                'field1'       => null,
                'field2'       => null,
                'association1' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'id'        => null,
                        'field1'    => null,
                        'field2'    => null,
                        '__class__' => null,
                    ]
                ],
                'association2' => [
                    'exclusion_policy' => 'all',
                    'property_path'    => 'realAssociation2',
                    'fields'           => [
                        'id'     => null,
                        'field1' => null,
                        'field2' => null,
                    ]
                ],
                'association3' => [
                    'exclusion_policy'       => 'all',
                    'fields'                 => [
                        'id'     => null,
                        'field1' => null,
                    ]
                ],
            ]
        ];

        $this->context->setExtras(
            [
                new FilterFieldsConfigExtra(
                    [
                        'primary_entity'       => ['field1', 'association1', 'association2', 'association3'],
                        'association_1_entity' => ['id', 'field1'],
                        'association_2_entity' => ['field2'],
                    ]
                )
            ]
        );

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->exactly(3))
            ->method('hasAssociation')
            ->willReturnMap(
                [
                    ['association1', true],
                    ['realAssociation2', true],
                    ['association3', true],
                ]
            );
        $rootEntityMetadata->expects($this->exactly(3))
            ->method('getAssociationTargetClass')
            ->willReturnMap(
                [
                    ['association1', 'Test\Association1Target'],
                    ['realAssociation2', 'Test\Association2Target'],
                    ['association3', 'Test\Association3Target'],
                ]
            );

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $association2Metadata = $this->getClassMetadataMock('Test\Association2Target');
        $association2Metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $association3Metadata = $this->getClassMetadataMock('Test\Association3Target');
        $association3Metadata->expects($this->never())
            ->method('getIdentifierFieldNames');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(4))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Association1Target', true, $association1Metadata],
                    ['Test\Association2Target', true, $association2Metadata],
                    ['Test\Association3Target', true, $association3Metadata],
                ]
            );

        $this->valueNormalizer->expects($this->exactly(3))
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    [
                        'primary_entity',
                        DataType::ENTITY_CLASS,
                        $this->context->getRequestType(),
                        false,
                        self::TEST_CLASS_NAME
                    ],
                    [
                        'association_1_entity',
                        DataType::ENTITY_CLASS,
                        $this->context->getRequestType(),
                        false,
                        'Test\Association1Target'
                    ],
                    [
                        'association_2_entity',
                        DataType::ENTITY_CLASS,
                        $this->context->getRequestType(),
                        false,
                        'Test\Association2Target'
                    ],
                ]
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'           => null,
                    'field1'       => null,
                    'field2'       => [
                        'exclude' => true
                    ],
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id'        => null,
                            'field1'    => null,
                            'field2'    => [
                                'exclude' => true
                            ],
                            '__class__' => null,
                        ]
                    ],
                    'association2' => [
                        'exclusion_policy' => 'all',
                        'property_path'    => 'realAssociation2',
                        'fields'           => [
                            'id'     => null,
                            'field1' => [
                                'exclude' => true
                            ],
                            'field2' => null,
                        ]
                    ],
                    'association3' => [
                        'exclusion_policy'       => 'all',
                        'fields'                 => [
                            'id'     => null,
                            'field1' => null,
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenTargetEntityUsesTableInheritance()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'id'     => null,
                        'field1' => null,
                        'field2' => null,
                    ]
                ],
            ]
        ];

        $this->context->setExtras(
            [
                new FilterFieldsConfigExtra(
                    [
                        'primary_entity'         => ['field1', 'association1'],
                        'association_1_1_entity' => ['id', 'field1'],
                    ]
                )
            ]
        );

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $rootEntityMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with('association1')
            ->willReturn('Test\Association1Target');

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $association1Metadata->subClasses = ['Test\Association1Target1', 'Test\Association1Target2'];
        $association1Metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Association1Target', true, $association1Metadata],
                ]
            );

        $this->valueNormalizer->expects($this->exactly(2))
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    [
                        'primary_entity',
                        DataType::ENTITY_CLASS,
                        $this->context->getRequestType(),
                        false,
                        self::TEST_CLASS_NAME
                    ],
                    [
                        'association_1_1_entity',
                        DataType::ENTITY_CLASS,
                        $this->context->getRequestType(),
                        false,
                        'Test\Association1Target'
                    ],
                    [
                        'association_2_entity',
                        DataType::ENTITY_CLASS,
                        $this->context->getRequestType(),
                        false,
                        'Test\Association1Target1'
                    ],
                ]
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id'     => null,
                            'field1' => null,
                            'field2' => [
                                'exclude' => true
                            ],
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }
}
