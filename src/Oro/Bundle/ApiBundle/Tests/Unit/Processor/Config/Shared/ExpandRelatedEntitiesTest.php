<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\ExpandRelatedEntities;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class ExpandRelatedEntitiesTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityOverrideProviderInterface */
    private $entityOverrideProvider;

    /** @var ExpandRelatedEntities */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);

        $entityOverrideProviderRegistry = $this->createMock(EntityOverrideProviderRegistry::class);
        $entityOverrideProviderRegistry->expects(self::any())
            ->method('getEntityOverrideProvider')
            ->with($this->context->getRequestType())
            ->willReturn($this->entityOverrideProvider);

        $this->processor = new ExpandRelatedEntities(
            $this->doctrineHelper,
            $this->configProvider,
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

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\NotSupportedConfigOperationException
     * @expectedExceptionMessage Requested unsupported operation "expand_related_entities" when building config for "Test\Class".
     */
    // @codingStandardsIgnoreEnd
    public function testProcessForDisabledInclusion()
    {
        $config = [
            'disable_inclusion' => true
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

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
                ]
            ]
        ];

        $this->context->setExtras(
            [
                new ExpandRelatedEntitiesConfigExtra(
                    ['field1', 'association1', 'association2', 'association3', 'association4']
                ),
                new TestConfigSection('test_section')
            ]
        );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityMetadataForClass');

        $this->configProvider->expects(self::exactly(3))
            ->method('getConfig')
            ->willReturnMap(
                [
                    [
                        'Test\Association1Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(['exclusion_policy' => 'all'], ['attr' => 'val'])
                    ],
                    [
                        'Test\Association2Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(['exclusion_policy' => 'all'], ['attr' => 'val'])
                    ],
                    [
                        'Test\Association3Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(['exclusion_policy' => 'all'])
                    ]
                ]
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'field1'       => null,
                    'association1' => [
                        'target_class'     => 'Test\Association1Target',
                        'exclusion_policy' => 'all',
                        'test_section'     => ['attr' => 'val']
                    ],
                    'association2' => [
                        'target_class'     => 'Test\Association2Target',
                        'property_path'    => 'realAssociation2',
                        'exclusion_policy' => 'all',
                        'test_section'     => ['attr' => 'val']
                    ],
                    'association3' => [
                        'target_class'     => 'Test\Association3Target',
                        'exclusion_policy' => 'all'
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
                'association2' => null,
                'association3' => [
                    'property_path' => 'realAssociation3'
                ]
            ]
        ];

        $this->context->setExtras(
            [
                new ExpandRelatedEntitiesConfigExtra(
                    ['field1', 'association1', 'association2', 'association3', 'association4']
                ),
                new TestConfigSection('test_section')
            ]
        );

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::exactly(5))
            ->method('hasAssociation')
            ->willReturnMap(
                [
                    ['field1', false],
                    ['association1', true],
                    ['association2', true],
                    ['realAssociation3', true],
                    ['association4', true]
                ]
            );
        $rootEntityMetadata->expects(self::exactly(4))
            ->method('getAssociationTargetClass')
            ->willReturnMap(
                [
                    ['association1', 'Test\Association1Target'],
                    ['association2', 'Test\Association2Target'],
                    ['realAssociation3', 'Test\Association3Target'],
                    ['association4', 'Test\Association4Target']
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

        $this->configProvider->expects(self::exactly(4))
            ->method('getConfig')
            ->willReturnMap(
                [
                    [
                        'Test\Association1Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(['exclusion_policy' => 'all'], ['attr' => 'val'])
                    ],
                    [
                        'Test\Association2Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(['exclusion_policy' => 'all'], ['attr' => 'val'])
                    ],
                    [
                        'Test\Association3Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(['exclusion_policy' => 'all'])
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

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'target_class'     => 'Test\Association1Target',
                        'target_type'      => 'to-one',
                        'test_section'     => ['attr' => 'val']
                    ],
                    'association2' => [
                        'exclusion_policy' => 'all',
                        'target_class'     => 'Test\Association2Target',
                        'target_type'      => 'to-one',
                        'test_section'     => ['attr' => 'val']
                    ],
                    'association3' => [
                        'exclusion_policy' => 'all',
                        'property_path'    => 'realAssociation3',
                        'target_type'      => 'to-one',
                        'target_class'     => 'Test\Association3Target'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForManageableEntityWithPropertyPath()
    {
        $entityDefinition = $this->createConfigObject([
            'fields' => [
                'account'             => [
                    'property_path' => 'customerAssociation.account'
                ],
                'customerAssociation' => [
                    'fields' => [
                        'account' => null
                    ]
                ]
            ]
        ]);
        $this->context->setResult($entityDefinition);
        $this->context->setExtras(
            [
                new ExpandRelatedEntitiesConfigExtra(['account']),
                new TestConfigSection('test_section')
            ]
        );

        $this->configProvider->expects(self::any())
            ->method('getConfig')
            ->with(
                'Account',
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new TestConfigSection('test_section')]
            )
            ->willReturn($this->createRelationConfigObject([]));

        $metadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        new ClassMetadata(self::TEST_CLASS_NAME);
        $metadata->associationMappings = [
            'customerAssociation' => [
                'fieldName'    => 'customerAssociation',
                'type'         => ClassMetadata::MANY_TO_ONE,
                'targetEntity' => 'CustomerAssociation'
            ]
        ];
        $customerAssociationMetadata = new ClassMetadata('CustomerAssociation');
        $customerAssociationMetadata->associationMappings = [
            'account' => [
                'fieldName'    => 'account',
                'type'         => ClassMetadata::MANY_TO_ONE,
                'targetEntity' => 'Account'
            ]
        ];
        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($metadata);
        $this->doctrineHelper->expects(self::once())
            ->method('findEntityMetadataByPath')
            ->with(self::TEST_CLASS_NAME, 'customerAssociation')
            ->willReturn($customerAssociationMetadata);

        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'account'             => [
                        'property_path' => 'customerAssociation.account',
                        'target_class'  => 'Account',
                        'target_type'   => 'to-one'
                    ],
                    'customerAssociation' => [
                        'fields' => [
                            'account' => null
                        ]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenThirdLevelEntityShouldBeExpanded()
    {
        $config = [];

        $this->context->setExtras(
            [
                new ExpandRelatedEntitiesConfigExtra(['association1.association11'])
            ]
        );

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('association1')
            ->willReturn('Test\Association1Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType(),
                array_merge(
                    $this->context->getPropagableExtras(),
                    [new ExpandRelatedEntitiesConfigExtra(['association11'])]
                )
            )
            ->willReturn($this->createRelationConfigObject(['exclusion_policy' => 'all']));

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'target_class'     => 'Test\Association1Target',
                        'target_type'      => 'to-one'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForAssociationDoesNotExistInEntityAndConfiguredByTargetClassAndTargetType()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'target_class' => 'Test\Association1Target',
                    'target_type'  => 'to-one'
                ]
            ]
        ];

        $this->context->setExtras([new ExpandRelatedEntitiesConfigExtra(['association1'])]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->willReturn(false);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $this->context->getPropagableExtras()
            )
            ->willReturn($this->createRelationConfigObject(['exclusion_policy' => 'all']));

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'target_class'     => 'Test\Association1Target',
                        'target_type'      => 'to-one'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForAssociationWithTargetClassAndTargetTypeAndDataType()
    {
        $config = [
            'fields' => [
                'association1' => [
                    'data_type'    => 'some_custom_association',
                    'target_class' => 'Test\Association1Target',
                    'target_type'  => 'to-one'
                ]
            ]
        ];

        $this->context->setExtras([new ExpandRelatedEntitiesConfigExtra(['association1'])]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->willReturn(false);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'data_type'    => 'some_custom_association',
                        'target_class' => 'Test\Association1Target',
                        'target_type'  => 'to-one'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForManageableEntityWithAssociationToOverriddenEntity()
    {
        $this->context->setExtras([new ExpandRelatedEntitiesConfigExtra(['association1'])]);

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('association1')
            ->willReturn('Test\Association1Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->entityOverrideProvider->expects(self::once())
            ->method('getSubstituteEntityClass')
            ->with('Test\Association1Target')
            ->willReturn('Test\Association1SubstituteTarget');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                'Test\Association1SubstituteTarget',
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $this->context->getPropagableExtras()
            )
            ->willReturn($this->createRelationConfigObject(['exclusion_policy' => 'all']));

        $this->context->setResult($this->createConfigObject([]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'target_class'     => 'Test\Association1SubstituteTarget',
                        'target_type'      => 'to-one'
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
