<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinitionOfAssociationsByConfig;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\RelationConfigProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class CompleteDefinitionOfAssociationsByConfigTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RelationConfigProvider */
    private $relationConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityOverrideProviderInterface */
    private $entityOverrideProvider;

    /** @var CompleteDefinitionOfAssociationsByConfig */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->relationConfigProvider = $this->createMock(RelationConfigProvider::class);
        $this->entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);

        $entityOverrideProviderRegistry = $this->createMock(EntityOverrideProviderRegistry::class);
        $entityOverrideProviderRegistry->expects(self::any())
            ->method('getEntityOverrideProvider')
            ->with($this->context->getRequestType())
            ->willReturn($this->entityOverrideProvider);

        $this->processor = new CompleteDefinitionOfAssociationsByConfig(
            $this->doctrineHelper,
            $this->relationConfigProvider,
            $entityOverrideProviderRegistry
        );
    }

    public function testProcessForAlreadyProcessedConfig()
    {
        $config = [
            'exclusion_policy' => 'all'
        ];

        $this->doctrineHelper->expects(self::never())
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
                ]
            ]
        ];

        $this->context->setExtras(
            [
                $this->createMock(ConfigExtraInterface::class),
                new TestConfigSection('test_section')
            ]
        );

        $this->relationConfigProvider->expects(self::exactly(4))
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
                    ]
                ]
            );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects(self::never())
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
                    ]
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
                ]
            ]
        ];

        $this->context->setExtras(
            [
                $this->createMock(ConfigExtraInterface::class),
                new TestConfigSection('test_section')
            ]
        );

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn(
                [
                    'association1'     => [
                        'targetEntity' => 'Test\Association1Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ],
                    'association2'     => [
                        'targetEntity' => 'Test\Association2Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ],
                    'association3'     => [
                        'targetEntity' => 'Test\Association3Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ],
                    'association4'     => [
                        'targetEntity' => 'Test\Association4Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ],
                    'realAssociation5' => [
                        'targetEntity' => 'Test\Association5Target',
                        'type'         => ClassMetadata::MANY_TO_ONE
                    ]
                ]
            );

        $this->relationConfigProvider->expects(self::exactly(4))
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
                    ]
                ]
            );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
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
                        'target_class'     => 'Test\Association2Target',
                        'target_type'      => 'to-one',
                        'fields'           => [
                            'id' => null
                        ],
                        'test_section'     => ['test']
                    ],
                    'association3' => [
                        'exclusion_policy' => 'all',
                        'target_class'     => 'Test\Association3Target',
                        'target_type'      => 'to-one',
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
                        'target_class'     => 'Test\Association5Target',
                        'target_type'      => 'to-one',
                        'collapse'         => true,
                        'fields'           => [
                            'id' => null
                        ]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForManageableEntityWithAssociationToOverriddenEntity()
    {
        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMappings')
            ->willReturn([
                'association1' => [
                    'targetEntity' => 'Test\Association1Target',
                    'type'         => ClassMetadata::MANY_TO_ONE
                ]
            ]);

        $this->entityOverrideProvider->expects(self::once())
            ->method('getSubstituteEntityClass')
            ->with('Test\Association1Target')
            ->willReturn('Test\Association1SubstituteTarget');

        $this->relationConfigProvider->expects(self::once())
            ->method('getRelationConfig')
            ->with(
                'Test\Association1SubstituteTarget',
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $this->context->getPropagableExtras()
            )
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'exclusion_policy' => 'all',
                        'collapse'         => true,
                        'fields'           => [
                            'id' => null
                        ]
                    ]
                )
            );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->context->setResult($this->createConfigObject([]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'target_class'     => 'Test\Association1SubstituteTarget',
                        'target_type'      => 'to-one',
                        'collapse'         => true,
                        'fields'           => [
                            'id' => null
                        ]
                    ]
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
    private function createRelationConfigObject(array $definition = null, array $testSection = null)
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
