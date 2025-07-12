<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\NormalizeMetadata;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NormalizeMetadataTest extends MetadataProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataProvider */
    private $metadataProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityOverrideProviderInterface */
    private $entityOverrideProvider;

    /** @var NormalizeMetadata */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);
        $this->entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);

        $entityOverrideProviderRegistry = $this->createMock(EntityOverrideProviderRegistry::class);
        $entityOverrideProviderRegistry->expects(self::any())
            ->method('getEntityOverrideProvider')
            ->willReturn($this->entityOverrideProvider);

        $this->processor = new NormalizeMetadata(
            $this->doctrineHelper,
            new EntityMetadataFactory($this->doctrineHelper),
            $this->metadataProvider,
            $entityOverrideProviderRegistry
        );
    }

    protected function createFieldMetadata(string $fieldName, ?string $dataType = null): FieldMetadata
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);
        if ($dataType) {
            $fieldMetadata->setDataType($dataType);
        }
        $fieldMetadata->setIsNullable(false);

        return $fieldMetadata;
    }

    protected function createAssociationMetadata(
        string $associationName,
        string $targetClass,
        ?string $associationType = null,
        ?bool $isCollection = null,
        ?string $dataType = null,
        ?array $acceptableTargetClasses = null,
        bool $collapsed = false
    ): AssociationMetadata {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        if (null !== $associationType) {
            $associationMetadata->setAssociationType($associationType);
        }
        if (null !== $isCollection) {
            $associationMetadata->setIsCollection($isCollection);
        }
        if (null !== $dataType) {
            $associationMetadata->setDataType($dataType);
        }
        if (null !== $acceptableTargetClasses) {
            $associationMetadata->setAcceptableTargetClassNames($acceptableTargetClasses);
        }
        $associationMetadata->setIsNullable(false);
        $associationMetadata->setCollapsed($collapsed);

        return $associationMetadata;
    }

    public function testProcessWithoutMetadata()
    {
        $this->processor->process($this->context);
    }

    public function testProcessNormalizationWithoutLinkedProperties()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'       => null,
                'field2'       => [
                    'exclude' => true
                ],
                'field3'       => [
                    'property_path' => 'realField3'
                ],
                'association1' => null,
                'association2' => [
                    'exclude' => true
                ],
                'association3' => [
                    'property_path' => 'realAssociation3'
                ]
            ]
        ];

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->addField($this->createFieldMetadata('field1'));
        $metadata->addField($this->createFieldMetadata('field2'));
        $metadata->addField($this->createFieldMetadata('field3'));
        $metadata->addField($this->createFieldMetadata('field4'));
        $metadata->addAssociation(
            $this->createAssociationMetadata('association1', 'Test\Association1Target')
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('association2', 'Test\Association2Target')
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('association3', 'Test\Association3Target')
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('association4', 'Test\Association4Target')
        );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->context->setConfig($this->createConfigObject($config));
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata('Test\Entity');
        $expectedMetadata->addField($this->createFieldMetadata('field1'));
        $expectedMetadata->addField($this->createFieldMetadata('field3'));
        $expectedMetadata->addAssociation(
            $this->createAssociationMetadata('association1', 'Test\Association1Target')
        );
        $expectedMetadata->addAssociation(
            $this->createAssociationMetadata('association3', 'Test\Association3Target')
        );

        self::assertEquals($expectedMetadata, $this->context->getResult());
    }

    public function testProcessWhenExcludedPropertiesShouldNotBeRemoved()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclude' => true
                ]
            ]
        ];

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->addField($this->createFieldMetadata('field1'));
        $metadata->addField($this->createFieldMetadata('field2'));

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->context->setConfig($this->createConfigObject($config));
        $this->context->setResult($metadata);
        $this->context->setWithExcludedProperties(true);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata('Test\Entity');
        $expectedMetadata->addField($this->createFieldMetadata('field1'));
        $expectedMetadata->addField($this->createFieldMetadata('field2'));

        self::assertEquals($expectedMetadata, $this->context->getResult());
    }

    public function testProcessLinkedPropertiesForFieldWithoutPropertyPath()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];
        $configObject = $this->createConfigObject($config);

        $metadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $metadata->addField($this->createFieldMetadata('field1'));

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->context->setConfig($configObject);
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $expectedMetadata->addField($this->createFieldMetadata('field1'));

        self::assertEquals($expectedMetadata, $this->context->getResult());
    }

    public function testProcessLinkedPropertiesForRenamedField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field2' => [
                    'property_path' => 'realField2'
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);

        $metadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $metadata->addField($this->createFieldMetadata('field2'));

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->context->setConfig($configObject);
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $expectedMetadata->addField($this->createFieldMetadata('field2'));

        self::assertEquals($expectedMetadata, $this->context->getResult());
    }

    public function testProcessLinkedPropertiesWhenItIsAlreadyProcessed()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association3' => [
                    'property_path' => 'association31.association311'
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);

        $metadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $metadata->addAssociation(
            $this->createAssociationMetadata('association3', 'Test\Association3Target')
        );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->context->setConfig($configObject);
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $expectedMetadata->addAssociation(
            $this->createAssociationMetadata('association3', 'Test\Association3Target')
        );

        self::assertEquals($expectedMetadata, $this->context->getResult());
    }

    public function testProcessLinkedPropertiesForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association3' => [
                    'property_path' => 'association31.association311'
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);

        $metadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $metadata->addAssociation(
            $this->createAssociationMetadata('association3', 'Test\Association3Target')
        );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setConfig($configObject);
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $expectedMetadata->addAssociation(
            $this->createAssociationMetadata('association3', 'Test\Association3Target')
        );

        self::assertEquals($expectedMetadata, $this->context->getResult());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessLinkedPropertiesForAssociationWithPropertyPathAndHasConfigForTargetField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association4' => [
                    'property_path' => 'association41.association411',
                    'direction'     => 'input-only',
                    'fields'        => [
                        'association411' => [
                            'fields' => [
                                'id' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);

        $metadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $metadata->addAssociation(
            $this->createAssociationMetadata('association411', 'Test\Association411Target')
        );

        $association41ClassMetadata = $this->getClassMetadataMock('Test\Association41Target');
        $association41ClassMetadata->fieldMappings = [];
        $association41ClassMetadata->associationMappings = [
            'association411' => [
                'type'         => ClassMetadata::MANY_TO_ONE,
                'targetEntity' => 'Test\Association411Target'
            ]
        ];

        $association411ClassMetadata = $this->getClassMetadataMock('Test\Association411Target');
        $association411ClassMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $association411ClassMetadata->expects(self::once())
            ->method('isInheritanceTypeNone')
            ->willReturn(true);
        $association411ClassMetadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('integer');
        $association411ClassMetadata->fieldMappings = ['id' => ['type' => 'integer']];
        $association411ClassMetadata->associationMappings = [];

        $association411TargetMetadata = new EntityMetadata('Test\Association411Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('findEntityMetadataByPath')
            ->with(self::TEST_CLASS_NAME, ['association41'])
            ->willReturn($association41ClassMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with('Test\Association411Target')
            ->willReturn($association411ClassMetadata);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                'Test\Association411Target',
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $configObject->getField('association4')->getTargetEntity(),
                $this->context->getExtras(),
                false
            )
            ->willReturn($association411TargetMetadata);

        $this->context->setConfig($configObject);
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $expectedAssociation4 = $this->createAssociationMetadata(
            'association4',
            'Test\Association411Target',
            'manyToOne',
            false,
            'integer',
            ['Test\Association411Target']
        );
        $expectedAssociation4->setPropertyPath('association41.association411');
        $expectedAssociation4->setIsNullable(true);
        $expectedAssociation4->setTargetMetadata($association411TargetMetadata);
        $expectedAssociation4->setDirection(true, false);
        $expectedMetadata->addAssociation($expectedAssociation4);

        self::assertEquals($expectedMetadata, $this->context->getResult());
    }

    public function testProcessLinkedPropertiesForAssociationWithPropertyPathAndWithoutConfigForTargetField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field5' => [
                    'property_path' => 'association51.field511'
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);

        $metadata = new EntityMetadata(self::TEST_CLASS_NAME);

        $association51ClassMetadata = $this->getClassMetadataMock('Test\Association51Target');
        $association51ClassMetadata->fieldMappings = ['field511' => ['type' => 'string']];
        $association51ClassMetadata->associationMappings = [];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('findEntityMetadataByPath')
            ->with(self::TEST_CLASS_NAME, ['association51'])
            ->willReturn($association51ClassMetadata);

        $this->context->setConfig($configObject);
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $expectedField5 = $expectedMetadata->addField($this->createFieldMetadata('field5', 'string'));
        $expectedField5->setPropertyPath('association51.field511');

        self::assertEquals($expectedMetadata, $this->context->getResult());
    }

    public function testProcessLinkedPropertiesWithPropertyPathButWhenIntermediateFieldIsNotAssociation()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field6' => [
                    'property_path' => 'field61.field611'
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);

        $metadata = new EntityMetadata(self::TEST_CLASS_NAME);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('findEntityMetadataByPath')
            ->with(self::TEST_CLASS_NAME, ['field61'])
            ->willReturn(null);

        $this->context->setConfig($configObject);
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata(self::TEST_CLASS_NAME);

        self::assertEquals($expectedMetadata, $this->context->getResult());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessRenamedLinkedPropertyWhenItIsField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'linkedField1' => [
                    'property_path' => 'realAssociation1.realField11'
                ],
                'association1'       => [
                    'exclude'       => true,
                    'property_path' => 'realAssociation1',
                    'target_class'  => 'Test\AssociationTarget',
                    'fields'        => [
                        'field11' => [
                            'property_path' => 'realField11'
                        ]
                    ]
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);

        $metadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $metadata->addAssociation(
            $this->createAssociationMetadata(
                'association1',
                'Test\Association1Target',
                'manyToOne',
                false,
                'integer',
                ['Test\Association1Target']
            )
        );

        $association1TargetMetadata = new EntityMetadata('Test\Association11Target');
        $field11Metadata = $this->createFieldMetadata('field11', 'integer');
        $field11Metadata->setPropertyPath('realField11');
        $association1TargetMetadata->addField($field11Metadata);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                'Test\AssociationTarget',
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $configObject->getField('association1')->getTargetEntity(),
                $this->context->getExtras(),
                false
            )
            ->willReturn($association1TargetMetadata);

        $this->context->setConfig($configObject);
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $expectedLinkedField1 = $this->createFieldMetadata('linkedField1', 'integer');
        $expectedLinkedField1->setIsNullable(false);
        $expectedMetadata->addField($expectedLinkedField1);

        self::assertEquals($expectedMetadata, $this->context->getResult());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessRenamedLinkedPropertyWhenItIsAssociation()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'linkedAssociation1' => [
                    'property_path' => 'realAssociation1.realAssociation11'
                ],
                'association1'       => [
                    'exclude'       => true,
                    'property_path' => 'realAssociation1',
                    'fields'        => [
                        'association11' => [
                            'property_path' => 'realAssociation11',
                            'fields'        => [
                                'id' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);

        $metadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $metadata->addAssociation(
            $this->createAssociationMetadata(
                'association1',
                'Test\Association1Target',
                'manyToOne',
                false,
                'integer',
                ['Test\Association1Target']
            )
        );

        $association1ClassMetadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1ClassMetadata->fieldMappings = [];
        $association1ClassMetadata->associationMappings = [
            'realAssociation11' => [
                'type'         => ClassMetadata::MANY_TO_ONE,
                'targetEntity' => 'Test\Association11Target'
            ]
        ];

        $association11ClassMetadata = $this->getClassMetadataMock('Test\Association11Target');
        $association11ClassMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $association11ClassMetadata->expects(self::once())
            ->method('isInheritanceTypeNone')
            ->willReturn(true);
        $association11ClassMetadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('integer');
        $association11ClassMetadata->fieldMappings = ['id' => ['type' => 'integer']];
        $association11ClassMetadata->associationMappings = [];

        $association11TargetMetadata = new EntityMetadata('Test\Association11Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('findEntityMetadataByPath')
            ->with(self::TEST_CLASS_NAME, ['realAssociation1'])
            ->willReturn($association1ClassMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with('Test\Association11Target')
            ->willReturn($association11ClassMetadata);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                'Test\Association11Target',
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $configObject
                    ->getField('association1')
                    ->getTargetEntity()
                    ->getField('association11')
                    ->getTargetEntity(),
                $this->context->getExtras(),
                false
            )
            ->willReturn($association11TargetMetadata);

        $this->context->setConfig($configObject);
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $expectedLinkedAssociation1 = $this->createAssociationMetadata(
            'linkedAssociation1',
            'Test\Association11Target',
            'manyToOne',
            false,
            'integer',
            ['Test\Association11Target']
        );
        $expectedLinkedAssociation1->setPropertyPath('realAssociation1.realAssociation11');
        $expectedLinkedAssociation1->setIsNullable(true);
        $expectedLinkedAssociation1->setTargetMetadata($association11TargetMetadata);
        $expectedMetadata->addAssociation($expectedLinkedAssociation1);

        self::assertEquals($expectedMetadata, $this->context->getResult());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessCollapsedArrayAssociationLinkedProperty()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'linkedAssociation1' => [
                    'property_path' => 'realAssociation1.realAssociation11'
                ],
                'association1'       => [
                    'exclude'       => true,
                    'property_path' => 'realAssociation1',
                    'fields'        => [
                        'association11' => [
                            'data_type'        => 'array',
                            'collapse'         => true,
                            'exclusion_policy' => 'all',
                            'property_path'    => 'realAssociation11',
                            'fields'           => [
                                'name' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);

        $metadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $metadata->addAssociation(
            $this->createAssociationMetadata(
                'association1',
                'Test\Association1Target',
                'manyToOne',
                false,
                'integer',
                ['Test\Association1Target']
            )
        );

        $association1ClassMetadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1ClassMetadata->fieldMappings = [];
        $association1ClassMetadata->associationMappings = [
            'realAssociation11' => [
                'type'         => ClassMetadata::MANY_TO_MANY,
                'targetEntity' => 'Test\Association11Target'
            ]
        ];

        $association11ClassMetadata = $this->getClassMetadataMock('Test\Association11Target');
        $association11ClassMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $association11ClassMetadata->expects(self::once())
            ->method('isInheritanceTypeNone')
            ->willReturn(true);
        $association11ClassMetadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('integer');
        $association11ClassMetadata->fieldMappings = ['id' => ['type' => 'integer']];
        $association11ClassMetadata->associationMappings = [];

        $association11TargetMetadata = new EntityMetadata('Test\Association11Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('findEntityMetadataByPath')
            ->with(self::TEST_CLASS_NAME, ['realAssociation1'])
            ->willReturn($association1ClassMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with('Test\Association11Target')
            ->willReturn($association11ClassMetadata);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                'Test\Association11Target',
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $configObject
                    ->getField('association1')
                    ->getTargetEntity()
                    ->getField('association11')
                    ->getTargetEntity(),
                $this->context->getExtras(),
                false
            )
            ->willReturn($association11TargetMetadata);

        $this->context->setConfig($configObject);
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $expectedLinkedAssociation1 = $this->createAssociationMetadata(
            'linkedAssociation1',
            'Test\Association11Target',
            'manyToMany',
            true,
            'array',
            ['Test\Association11Target'],
            true
        );
        $expectedLinkedAssociation1->setPropertyPath('realAssociation1.realAssociation11');
        $expectedLinkedAssociation1->setIsNullable(true);
        $expectedLinkedAssociation1->setTargetMetadata($association11TargetMetadata);
        $expectedMetadata->addAssociation($expectedLinkedAssociation1);

        self::assertEquals($expectedMetadata, $this->context->getResult());
    }

    public function testNormalizeAcceptableTargetClassNames()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'fields' => [
                        'association11' => [
                            'fields' => [
                                'name' => null
                            ]
                        ]
                    ]
                ],
                'association2' => [
                    'fields' => [
                        'name' => null
                    ]
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);

        $metadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $metadata->addAssociation(
            $this->createAssociationMetadata(
                'association1',
                'Test\Association1Target',
                'manyToOne',
                false,
                'integer',
                ['Test\Association1Target2', 'Test\Association1Target2']
            )
        );
        $association1Metadata = new EntityMetadata('Test\Entity');
        $association1Metadata->addAssociation(
            $this->createAssociationMetadata(
                'association11',
                EntityIdentifier::class,
                'manyToOne',
                false,
                'integer',
                ['Test\Association11Target1', 'Test\Association11Target2']
            )
        );
        $metadata->getAssociation('association1')->setTargetMetadata($association1Metadata);
        $metadata->addAssociation(
            $this->createAssociationMetadata(
                'association2',
                EntityIdentifier::class,
                'manyToOne',
                false,
                'integer',
                ['Test\Association2Target1', 'Test\Association2Target2']
            )
        );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->entityOverrideProvider->expects(self::exactly(4))
            ->method('getSubstituteEntityClass')
            ->willReturnMap([
                ['Test\Association11Target1', null],
                ['Test\Association11Target2', 'Test\Association11SubstituteTarget2'],
                ['Test\Association2Target1', 'Test\Association2SubstituteTarget1'],
                ['Test\Association2Target2', null]
            ]);

        $this->context->setConfig($configObject);
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        self::assertEquals(
            ['Test\Association11Target1', 'Test\Association11SubstituteTarget2'],
            $metadata
                ->getAssociation('association1')
                ->getTargetMetadata()
                ->getAssociation('association11')
                ->getAcceptableTargetClassNames()
        );
        self::assertEquals(
            ['Test\Association2SubstituteTarget1', 'Test\Association2Target2'],
            $metadata
                ->getAssociation('association2')
                ->getAcceptableTargetClassNames()
        );
    }

    public function testNormalizeExpandedNestedAssociation()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'            => [
                    'property_path' => 'targetAssociation.field11',
                    'direction'     => 'input-only'
                ],
                'association1'      => [
                    'property_path' => 'targetAssociation.association11',
                    'direction'     => 'output-only'
                ],
                'targetAssociation' => [
                    'exclude'      => true,
                    'target_class' => 'Test\AssociationTarget',
                    'fields'       => [
                        'field11'       => null,
                        'association11' => null
                    ]
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);

        $metadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $metadata->addAssociation(
            $this->createAssociationMetadata(
                'targetAssociation',
                'Test\AssociationTarget',
                'manyToOne',
                false,
                'integer'
            )
        );

        $targetMetadata = new EntityMetadata('Test\AssociationTarget');
        $targetMetadata->addField(
            $this->createFieldMetadata('field11', 'string')
        );
        $targetMetadata->addAssociation(
            $this->createAssociationMetadata(
                'association11',
                'Test\Association11Target',
                'manyToOne',
                false,
                'integer'
            )
        );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::never())
            ->method('findEntityMetadataByPath');
        $this->metadataProvider->expects(self::exactly(2))
            ->method('getMetadata')
            ->with(
                'Test\AssociationTarget',
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $configObject->getField('targetAssociation')->getTargetEntity(),
                $this->context->getExtras()
            )
            ->willReturn($targetMetadata);

        $this->context->setConfig($configObject);
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $field1Metadata = clone $targetMetadata->getField('field11');
        $field1Metadata->setName('field1');
        $field1Metadata->setDirection(true, false);
        self::assertEquals($field1Metadata, $metadata->getField('field1'));
        $association1Metadata = clone $targetMetadata->getAssociation('association11');
        $association1Metadata->setName('association1');
        $association1Metadata->setDirection(false, true);
        self::assertEquals($association1Metadata, $metadata->getAssociation('association1'));
    }

    public function testNormalizeExpandedNestedAssociationWithRenamedFields()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'                   => [
                    'property_path' => 'targetAssociation.field11'
                ],
                'association1'             => [
                    'property_path' => 'targetAssociation.association11'
                ],
                'renamedTargetAssociation' => [
                    'property_path' => 'targetAssociation',
                    'exclude'       => true,
                    'target_class'  => 'Test\AssociationTarget',
                    'fields'        => [
                        'renamedField11'       => [
                            'property_path' => 'field11'
                        ],
                        'renamedAssociation11' => [
                            'property_path' => 'association11'
                        ]
                    ]
                ]
            ]
        ];
        $configObject = $this->createConfigObject($config);

        $metadata = new EntityMetadata(self::TEST_CLASS_NAME);
        $metadata->addAssociation(
            $this->createAssociationMetadata(
                'targetAssociation',
                'Test\AssociationTarget',
                'manyToOne',
                false,
                'integer'
            )
        );

        $targetMetadata = new EntityMetadata('Test\AssociationTarget');
        $targetMetadata->addField(
            $this->createFieldMetadata('field11', 'string')
        );
        $targetMetadata->addAssociation(
            $this->createAssociationMetadata(
                'association11',
                'Test\Association11Target',
                'manyToOne',
                false,
                'integer'
            )
        );

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::never())
            ->method('findEntityMetadataByPath');
        $this->metadataProvider->expects(self::exactly(2))
            ->method('getMetadata')
            ->with(
                'Test\AssociationTarget',
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $configObject->getField('renamedTargetAssociation')->getTargetEntity(),
                $this->context->getExtras()
            )
            ->willReturn($targetMetadata);

        $this->context->setConfig($configObject);
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $field1Metadata = clone $targetMetadata->getField('field11');
        $field1Metadata->setName('field1');
        self::assertEquals($field1Metadata, $metadata->getField('field1'));
        $association1Metadata = clone $targetMetadata->getAssociation('association11');
        $association1Metadata->setName('association1');
        self::assertEquals($association1Metadata, $metadata->getAssociation('association1'));
    }
}
