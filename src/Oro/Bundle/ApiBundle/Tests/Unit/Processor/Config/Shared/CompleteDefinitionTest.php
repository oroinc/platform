<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class CompleteDefinitionTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $exclusionProvider;

    /** @var CompleteDefinition */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->exclusionProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface');

        $this->processor = new CompleteDefinition(
            $this->doctrineHelper,
            $this->exclusionProvider
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

    public function testProcessForNotManageableEntity()
    {
        $config = [];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessAssociationsForManageableEntity()
    {
        $config = [
            'fields' => [
                'association1' => [],
                'association2' => [
                    'exclude' => true
                ],
                'association4' => [
                    'exclusion_policy' => 'none'
                ],
                'association6' => [
                    'exclude' => false
                ],
                'association7' => [
                    'property_path' => 'realAssociation7'
                ],
                'association8' => [
                    'property_path' => 'realAssociation8'
                ],
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $this->exclusionProvider->expects($this->exactly(6))
            ->method('isIgnoredRelation')
            ->willReturnMap(
                [
                    [$rootEntityMetadata, 'association1', false],
                    [$rootEntityMetadata, 'association3', true],
                    [$rootEntityMetadata, 'association4', false],
                    [$rootEntityMetadata, 'association5', false],
                    [$rootEntityMetadata, 'realAssociation7', false],
                    [$rootEntityMetadata, 'realAssociation8', false],
                ]
            );

        $rootEntityMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn([]);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1'     => [
                        'targetEntity' => 'Test\Association1Target'
                    ],
                    'association2'     => [
                        'targetEntity' => 'Test\Association2Target'
                    ],
                    'association3'     => [
                        'targetEntity' => 'Test\Association3Target'
                    ],
                    'association4'     => [
                        'targetEntity' => 'Test\Association4Target'
                    ],
                    'association5'     => [
                        'targetEntity' => 'Test\Association5Target'
                    ],
                    'association6'     => [
                        'targetEntity' => 'Test\Association6Target'
                    ],
                    'realAssociation7' => [
                        'targetEntity' => 'Test\Association7Target'
                    ],
                    'realAssociation8' => [
                        'targetEntity' => 'Test\Association8Target'
                    ],
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityIdentifierFieldNamesForClass')
            ->willReturn(['id']);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'collapse'         => true,
                        'fields'           => [
                            'id' => null
                        ]
                    ],
                    'association2' => [
                        'exclusion_policy' => 'all',
                        'collapse'         => true,
                        'exclude'          => true,
                        'fields'           => [
                            'id' => null
                        ]
                    ],
                    'association3' => [
                        'exclusion_policy' => 'all',
                        'collapse'         => true,
                        'exclude'          => true,
                        'fields'           => [
                            'id' => null
                        ]
                    ],
                    'association4' => [
                        'exclusion_policy' => 'all'
                    ],
                    'association5' => [
                        'exclusion_policy' => 'all',
                        'collapse'         => true,
                        'fields'           => [
                            'id' => null
                        ]
                    ],
                    'association6' => [
                        'exclusion_policy' => 'all',
                        'collapse'         => true,
                        'fields'           => [
                            'id' => null
                        ]
                    ],
                    'association7' => [
                        'property_path'    => 'realAssociation7',
                        'exclusion_policy' => 'all',
                        'collapse'         => true,
                        'fields'           => [
                            'id' => null
                        ]
                    ],
                    'association8' => [
                        'property_path'    => 'realAssociation8',
                        'exclusion_policy' => 'all',
                        'collapse'         => true,
                        'fields'           => [
                            'id' => null
                        ]
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }
}
