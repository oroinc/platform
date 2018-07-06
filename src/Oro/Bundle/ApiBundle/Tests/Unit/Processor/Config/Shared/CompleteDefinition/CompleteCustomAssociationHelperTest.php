<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared\CompleteDefinition;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition\CompleteAssociationHelper;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition\CompleteCustomAssociationHelper;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;

class CompleteCustomAssociationHelperTest extends CompleteDefinitionHelperTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AssociationManager */
    private $associationManager;

    /** @var CompleteCustomAssociationHelper */
    private $completeCustomAssociationHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->associationManager = $this->createMock(AssociationManager::class);

        $this->completeCustomAssociationHelper = new CompleteCustomAssociationHelper(
            $this->doctrineHelper,
            new CompleteAssociationHelper($this->configProvider),
            $this->associationManager
        );
    }

    public function testCompleteToOneExtendedAssociationWithoutAssociationKind()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'data_type' => 'association:manyToOne'
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'manyToOne', null)
            ->willReturn(['Test\TargetClass1' => 'field1']);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
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

        $this->completeCustomAssociationHelper->completeCustomAssociations(
            self::TEST_CLASS_NAME,
            $config,
            $version,
            $requestType
        );

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'data_type'              => 'association:manyToOne',
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-one',
                        'depends_on'             => ['field1'],
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
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

    public function testCompleteToManyExtendedAssociationWithAssociationKind()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'data_type' => 'association:manyToMany:kind'
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'manyToMany', 'kind')
            ->willReturn(['Test\TargetClass1' => 'field1']);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
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

        $this->completeCustomAssociationHelper->completeCustomAssociations(
            self::TEST_CLASS_NAME,
            $config,
            $version,
            $requestType
        );

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'data_type'              => 'association:manyToMany:kind',
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-many',
                        'depends_on'             => ['field1'],
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
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

    public function testCompleteMultipleManyToOneExtendedAssociation()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'data_type' => 'association:multipleManyToOne'
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'multipleManyToOne', null)
            ->willReturn(['Test\TargetClass1' => 'field1']);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
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

        $this->completeCustomAssociationHelper->completeCustomAssociations(
            self::TEST_CLASS_NAME,
            $config,
            $version,
            $requestType
        );

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'data_type'              => 'association:multipleManyToOne',
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-many',
                        'depends_on'             => ['field1'],
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
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

    public function testCompleteExtendedAssociationWithCustomTargetClass()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'data_type'    => 'association:manyToOne',
                    'target_class' => 'Test\TargetClass'
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'manyToOne', null)
            ->willReturn(['Test\TargetClass1' => 'field1']);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\TargetClass', $version, $requestType)
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

        $this->completeCustomAssociationHelper->completeCustomAssociations(
            self::TEST_CLASS_NAME,
            $config,
            $version,
            $requestType
        );

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclusion_policy'       => 'all',
                        'data_type'              => 'association:manyToOne',
                        'target_class'           => 'Test\TargetClass',
                        'target_type'            => 'to-one',
                        'identifier_field_names' => ['id'],
                        'depends_on'             => ['field1'],
                        'collapse'               => true,
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
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "target_type" option cannot be configured for "Test\Class::association1".
     */
    public function testCompleteExtendedAssociationWithCustomTargetType()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'data_type'   => 'association:manyToOne',
                    'target_type' => 'to-many'
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->completeCustomAssociationHelper->completeCustomAssociations(
            self::TEST_CLASS_NAME,
            $config,
            $version,
            $requestType
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "depends_on" option cannot be configured for "Test\Class::association1".
     */
    public function testCompleteExtendedAssociationWithCustomDependsOn()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'data_type'  => 'association:manyToOne',
                    'depends_on' => ['field1']
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->completeCustomAssociationHelper->completeCustomAssociations(
            self::TEST_CLASS_NAME,
            $config,
            $version,
            $requestType
        );
    }

    public function testCompleteExtendedAssociationWhenTargetsHaveNotStringIdentifier()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'data_type' => 'association:manyToOne'
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'manyToOne', null)
            ->willReturn(['Test\TargetClass1' => 'field1', 'Test\TargetClass2' => 'field2']);

        $target1EntityMetadata = $this->getClassMetadataMock('Test\TargetClass1');
        $target1EntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $target1EntityMetadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('integer');

        $target2EntityMetadata = $this->getClassMetadataMock('Test\TargetClass2');
        $target2EntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $target2EntityMetadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('integer');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    ['Test\TargetClass1', true, $target1EntityMetadata],
                    ['Test\TargetClass2', true, $target2EntityMetadata]
                ]
            );

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ]
                )
            );

        $this->completeCustomAssociationHelper->completeCustomAssociations(
            self::TEST_CLASS_NAME,
            $config,
            $version,
            $requestType
        );

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'data_type'              => 'association:manyToOne',
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-one',
                        'depends_on'             => ['field1', 'field2'],
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
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

    public function testCompleteExtendedAssociationWhenTargetsHaveDifferentTypesOfIdentifier()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'data_type' => 'association:manyToOne'
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'manyToOne', null)
            ->willReturn(['Test\TargetClass1' => 'field1', 'Test\TargetClass2' => 'field2']);

        $target1EntityMetadata = $this->getClassMetadataMock('Test\TargetClass1');
        $target1EntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $target1EntityMetadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('integer');

        $target2EntityMetadata = $this->getClassMetadataMock('Test\TargetClass2');
        $target2EntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $target2EntityMetadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('string');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    ['Test\TargetClass1', true, $target1EntityMetadata],
                    ['Test\TargetClass2', true, $target2EntityMetadata]
                ]
            );

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ]
                )
            );

        $this->completeCustomAssociationHelper->completeCustomAssociations(
            self::TEST_CLASS_NAME,
            $config,
            $version,
            $requestType
        );

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'data_type'              => 'association:manyToOne',
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-one',
                        'depends_on'             => ['field1', 'field2'],
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteExtendedAssociationWhenOneOfTargetHasCompositeIdentifier()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'data_type' => 'association:manyToOne'
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'manyToOne', null)
            ->willReturn(['Test\TargetClass1' => 'field1', 'Test\TargetClass2' => 'field2']);

        $target1EntityMetadata = $this->getClassMetadataMock('Test\TargetClass1');
        $target1EntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $target1EntityMetadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('integer');

        $target2EntityMetadata = $this->getClassMetadataMock('Test\TargetClass2');
        $target2EntityMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id1', 'id2']);
        $target2EntityMetadata->expects(self::never())
            ->method('getTypeOfField');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    ['Test\TargetClass1', true, $target1EntityMetadata],
                    ['Test\TargetClass2', true, $target2EntityMetadata]
                ]
            );

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn(
                $this->createRelationConfigObject(
                    [
                        'identifier_field_names' => ['id'],
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ]
                )
            );

        $this->completeCustomAssociationHelper->completeCustomAssociations(
            self::TEST_CLASS_NAME,
            $config,
            $version,
            $requestType
        );

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'data_type'              => 'association:manyToOne',
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-one',
                        'depends_on'             => ['field1', 'field2'],
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => [
                                'data_type' => 'string'
                            ]
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteExtendedAssociationWithEmptyTargets()
    {
        $config = $this->createConfigObject([
            'fields' => [
                'association1' => [
                    'data_type' => 'association:manyToOne'
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with(self::TEST_CLASS_NAME, null, 'manyToOne', null)
            ->willReturn([]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
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

        $this->completeCustomAssociationHelper->completeCustomAssociations(
            self::TEST_CLASS_NAME,
            $config,
            $version,
            $requestType
        );

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'data_type'              => 'association:manyToOne',
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-one',
                        'form_options'           => [
                            'mapped' => false
                        ],
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
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
}
