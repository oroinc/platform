<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinitionOfAssociationsByConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;

class CompleteDefinitionOfAssociationsByConfigTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $relationConfigProvider;

    /** @var CompleteDefinitionOfAssociationsByConfig */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->relationConfigProvider = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Provider\RelationConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new CompleteDefinitionOfAssociationsByConfig(
            $this->doctrineHelper,
            $this->relationConfigProvider
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'field1'       => null,
                'association1' => [
                    'target_class' => 'Test\Association1Target'
                ],
                'association2' => [
                    'target_class'  => 'Test\Association2Target',
                    'property_path' => 'realAssociation2'
                ],
                'association3' => [
                    'target_class' => 'Test\Association3Target'
                ],
                'association4' => [
                    'target_class' => 'Test\Association4Target'
                ],
                'association5' => [
                    'exclusion_policy' => 'all',
                    'target_class'     => 'Test\Association5Target'
                ],
            ]
        ];

        $this->context->setExtras(
            [
                $this->getMock('Oro\Bundle\ApiBundle\Config\ConfigExtraInterface'),
                new TestConfigSection('test_section')
            ]
        );

        $this->relationConfigProvider->expects($this->exactly(4))
            ->method('getRelationConfig')
            ->willReturnMap(
                [
                    [
                        'Test\Association1Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(
                            [
                                'exclusion_policy' => 'all',
                                'collapse'         => true,
                                'fields'           => [
                                    'id' => null
                                ]
                            ]
                        )
                    ],
                    [
                        'Test\Association2Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(
                            [
                                'exclusion_policy' => 'all',
                                'collapse'         => true,
                                'fields'           => [
                                    'id' => null
                                ]
                            ]
                        )
                    ],
                    [
                        'Test\Association3Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(
                            [
                                'exclusion_policy' => 'all',
                                'fields'           => [
                                    'id' => null
                                ]
                            ],
                            ['test']
                        )
                    ],
                    [
                        'Test\Association4Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject()
                    ],
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'field1'       => null,
                    'association1' => [
                        'target_class'     => 'Test\Association1Target',
                        'exclusion_policy' => 'all',
                        'collapse'         => true,
                        'fields'           => [
                            'id' => null
                        ]
                    ],
                    'association2' => [
                        'target_class'     => 'Test\Association2Target',
                        'property_path'    => 'realAssociation2',
                        'exclusion_policy' => 'all',
                        'collapse'         => true,
                        'fields'           => [
                            'id' => null
                        ]
                    ],
                    'association3' => [
                        'target_class'     => 'Test\Association3Target',
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id' => null
                        ],
                        'test_section'     => ['test']
                    ],
                    'association4' => [
                        'target_class' => 'Test\Association4Target'
                    ],
                    'association5' => [
                        'exclusion_policy' => 'all',
                        'target_class'     => 'Test\Association5Target'
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
            'fields' => [
                'association4' => [
                    'exclusion_policy' => 'all'
                ],
                'association5' => [
                    'property_path' => 'realAssociation5'
                ],
            ]
        ];

        $this->context->setExtras(
            [
                $this->getMock('Oro\Bundle\ApiBundle\Config\ConfigExtraInterface'),
                new TestConfigSection('test_section')
            ]
        );

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
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
                    'realAssociation5' => [
                        'targetEntity' => 'Test\Association5Target'
                    ],
                ]
            );

        $this->relationConfigProvider->expects($this->exactly(4))
            ->method('getRelationConfig')
            ->willReturnMap(
                [
                    [
                        'Test\Association1Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject()
                    ],
                    [
                        'Test\Association2Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(
                            [
                                'exclusion_policy' => 'all',
                                'fields'           => [
                                    'id' => null
                                ]
                            ],
                            ['test']
                        )
                    ],
                    [
                        'Test\Association3Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(
                            [
                                'exclusion_policy' => 'all',
                                'collapse'         => true,
                                'fields'           => [
                                    'id' => null
                                ]
                            ]
                        )
                    ],
                    [
                        'Test\Association5Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(
                            [
                                'exclusion_policy' => 'all',
                                'collapse'         => true,
                                'fields'           => [
                                    'id' => null
                                ]
                            ]
                        )
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

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association2' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id' => null
                        ],
                        'test_section'     => ['test']
                    ],
                    'association3' => [
                        'exclusion_policy' => 'all',
                        'collapse'         => true,
                        'fields'           => [
                            'id' => null
                        ]
                    ],
                    'association4' => [
                        'exclusion_policy' => 'all'
                    ],
                    'association5' => [
                        'property_path'    => 'realAssociation5',
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

    /**
     * @param array|null $definition
     * @param array|null $testSection
     *
     * @return Config
     */
    protected function createRelationConfigObject(array $definition = null, array $testSection = null)
    {
        $config = new Config();
        if (null !== $definition) {
            $config->setDefinition($this->createConfigObject($definition));
        }
        if (null !== $testSection) {
            $config->set('test_section', $testSection);
        }

        return $config;
    }
}
