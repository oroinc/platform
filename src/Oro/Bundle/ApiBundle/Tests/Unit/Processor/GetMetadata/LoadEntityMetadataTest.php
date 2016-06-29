<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\LoadEntityMetadata;

class LoadEntityMetadataTest extends MetadataProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var LoadEntityMetadata */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadEntityMetadata(
            $this->doctrineHelper,
            new EntityMetadataFactory($this->doctrineHelper)
        );
    }

    /**
     * @param string $fieldName
     * @param string $dataType
     *
     * @return FieldMetadata
     */
    protected function createFieldMetadata($fieldName, $dataType)
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);
        $fieldMetadata->setDataType($dataType);
        $fieldMetadata->setIsNullable(false);

        return $fieldMetadata;
    }

    /**
     * @param string   $associationName
     * @param string   $targetClass
     * @param bool     $isCollection
     * @param string   $dataType
     * @param string[] $acceptableTargetClasses
     *
     * @return AssociationMetadata
     */
    protected function createAssociationMetadata(
        $associationName,
        $targetClass,
        $isCollection,
        $dataType,
        array $acceptableTargetClasses
    ) {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setIsCollection($isCollection);
        $associationMetadata->setDataType($dataType);
        $associationMetadata->setAcceptableTargetClassNames($acceptableTargetClasses);
        $associationMetadata->setIsNullable(true);

        return $associationMetadata;
    }

    public function testProcessForAlreadyLoadedMetadata()
    {
        $metadata = new EntityMetadata();

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $this->assertSame($metadata, $this->context->getResult());
    }

    public function testProcessForNotManageableEntityWithoutConfig()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->processor->process($this->context);

        $this->assertNull($this->context->getResult());
    }

    public function testProcessForNotManageableEntityWithoutFieldsInConfig()
    {
        $config = [
            'exclusion_policy' => 'all',
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setConfig($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertNull($this->context->getResult());
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['field1'],
            'fields'                 => [
                'field1'       => [
                    'data_type' => 'integer'
                ],
                'field2'       => [
                    'data_type' => 'string',
                    'exclude'   => true
                ],
                'field3'       => [
                    'data_type'     => 'string',
                    'property_path' => 'realField3'
                ],
                'association1' => [
                    'target_class'           => 'Test\Association1Target',
                    'identifier_field_names' => ['id'],
                    'fields'                 => [
                        'id' => [
                            'data_type' => 'integer'
                        ]
                    ]
                ],
                'association2' => [
                    'target_class' => 'Test\Association2Target',
                    'exclude'      => true
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setConfig($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertNotNull($this->context->getResult());

        $expectedMetadata = new EntityMetadata();
        $expectedMetadata->setClassName(self::TEST_CLASS_NAME);
        $expectedMetadata->setIdentifierFieldNames(['field1']);
        $expectedMetadata->addField($this->createFieldMetadata('field1', 'integer'))->setIsNullable(false);
        $expectedMetadata->addField($this->createFieldMetadata('field3', 'string'))->setIsNullable(true);
        $expectedMetadata->addAssociation(
            $this->createAssociationMetadata(
                'association1',
                'Test\Association1Target',
                false,
                'integer',
                ['Test\Association1Target']
            )
        );

        $this->assertEquals($expectedMetadata, $this->context->getResult());
    }

    public function testProcessForManageableEntityWithoutConfig()
    {
        $classMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $classMetadata->expects($this->once())
            ->method('usesIdGenerator')
            ->willReturn(true);

        $classMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(
                [
                    'id',
                    'name',
                ]
            );
        $classMetadata->expects($this->exactly(2))
            ->method('getTypeOfField')
            ->willReturnMap(
                [
                    ['id', 'integer'],
                    ['name', 'string'],
                ]
            );
        $classMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($classMetadata);

        $this->processor->process($this->context);

        $this->assertNotNull($this->context->getResult());

        $expectedMetadata = new EntityMetadata();
        $expectedMetadata->setClassName(self::TEST_CLASS_NAME);
        $expectedMetadata->setInheritedType(false);
        $expectedMetadata->setIdentifierFieldNames(['id']);
        $expectedMetadata->setHasIdentifierGenerator(true);
        $expectedMetadata->addField($this->createFieldMetadata('id', 'integer'));
        $expectedMetadata->addField($this->createFieldMetadata('name', 'string'));

        $this->assertEquals($expectedMetadata, $this->context->getResult());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForManageableEntityWithConfig()
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
                ],
            ]
        ];

        $classMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['field1']);
        $classMetadata->expects($this->once())
            ->method('usesIdGenerator')
            ->willReturn(true);

        $classMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(
                [
                    'field1',
                    'field2',
                    'realField3',
                ]
            );
        $classMetadata->expects($this->exactly(2))
            ->method('getTypeOfField')
            ->willReturnMap(
                [
                    ['field1', 'integer'],
                    ['realField3', 'string'],
                ]
            );
        $classMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(
                [
                    'association1',
                    'association2',
                    'realAssociation3',
                ]
            );
        $classMetadata->expects($this->exactly(2))
            ->method('getAssociationTargetClass')
            ->willReturnMap(
                [
                    ['association1', 'Test\Association1Target'],
                    ['realAssociation3', 'Test\Association3Target'],
                ]
            );
        $classMetadata->expects($this->exactly(2))
            ->method('isCollectionValuedAssociation')
            ->willReturnMap(
                [
                    ['association1', false],
                    ['realAssociation3', true],
                ]
            );

        $association1ClassMetadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1ClassMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $association1ClassMetadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('integer');

        $association3ClassMetadata                  = $this->getClassMetadataMock('Test\Association3Target');
        $association3ClassMetadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $association3ClassMetadata->subClasses      = [
            'Test\Association3Target1',
            'Test\Association3Target2',
        ];
        $association3ClassMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['field1', 'field2']);
        $association3ClassMetadata->expects($this->never())
            ->method('getTypeOfField');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $classMetadata],
                    ['Test\Association1Target', true, $association1ClassMetadata],
                    ['Test\Association3Target', true, $association3ClassMetadata],
                ]
            );

        $this->context->setConfig($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertNotNull($this->context->getResult());

        $expectedMetadata = new EntityMetadata();
        $expectedMetadata->setClassName(self::TEST_CLASS_NAME);
        $expectedMetadata->setInheritedType(false);
        $expectedMetadata->setIdentifierFieldNames(['field1']);
        $expectedMetadata->setHasIdentifierGenerator(true);
        $expectedMetadata->addField($this->createFieldMetadata('field1', 'integer'));
        $expectedMetadata->addField($this->createFieldMetadata('field3', 'string'));
        $expectedMetadata->addAssociation(
            $this->createAssociationMetadata(
                'association1',
                'Test\Association1Target',
                false,
                'integer',
                ['Test\Association1Target']
            )
        );
        $expectedMetadata->addAssociation(
            $this->createAssociationMetadata(
                'association3',
                'Test\Association3Target',
                true,
                'string',
                ['Test\Association3Target1', 'Test\Association3Target2']
            )
        );

        $this->assertEquals($expectedMetadata, $this->context->getResult());
    }

    public function testProcessForManageableEntityWhenRenamedIdentifierField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'renamedId'       => [
                    'property_path' => 'realId'
                ],
            ]
        ];

        $classMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['realId']);
        $classMetadata->expects($this->once())
            ->method('usesIdGenerator')
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['realId']);
        $classMetadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('realId')
            ->willReturn('integer');
        $classMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, true)
            ->willReturn($classMetadata);

        $this->context->setConfig($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertNotNull($this->context->getResult());

        $expectedMetadata = new EntityMetadata();
        $expectedMetadata->setClassName(self::TEST_CLASS_NAME);
        $expectedMetadata->setInheritedType(false);
        $expectedMetadata->setIdentifierFieldNames(['renamedId']);
        $expectedMetadata->setHasIdentifierGenerator(true);
        $expectedMetadata->addField($this->createFieldMetadata('renamedId', 'integer'));

        $this->assertEquals($expectedMetadata, $this->context->getResult());
    }

    public function testProcessForManageableEntityWhenNoConfigurationForIdentifierField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'someField' => null
            ]
        ];

        $classMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $classMetadata->expects($this->once())
            ->method('usesIdGenerator')
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['id', 'someField']);
        $classMetadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('someField')
            ->willReturn('integer');
        $classMetadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, true)
            ->willReturn($classMetadata);

        $this->context->setConfig($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertNotNull($this->context->getResult());

        $expectedMetadata = new EntityMetadata();
        $expectedMetadata->setClassName(self::TEST_CLASS_NAME);
        $expectedMetadata->setInheritedType(false);
        $expectedMetadata->setIdentifierFieldNames(['id']);
        $expectedMetadata->setHasIdentifierGenerator(true);
        $expectedMetadata->addField($this->createFieldMetadata('someField', 'integer'));

        $this->assertEquals($expectedMetadata, $this->context->getResult());
    }
}
