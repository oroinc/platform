<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\MetadataHelper;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataFactory;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;

class ObjectMetadataFactoryTest extends LoaderTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $associationManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $fieldTypeHelper;

    /** @var ObjectMetadataFactory */
    protected $objectMetadataFactory;

    protected function setUp()
    {
        $this->associationManager = $this->getMockBuilder(AssociationManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldTypeHelper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectMetadataFactory = new ObjectMetadataFactory(
            new MetadataHelper(),
            $this->associationManager,
            $this->fieldTypeHelper
        );
    }

    public function testCreateObjectMetadata()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);

        $expected = new EntityMetadata();
        $expected->setClassName('Test\Class');
        $expected->setIdentifierFieldNames(['id']);

        self::assertEquals(
            $expected,
            $this->objectMetadataFactory->createObjectMetadata('Test\Class', $config)
        );
    }

    public function testCreateAndAddMetaPropertyMetadata()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setPropertyPath('propertyPath');

        $expected = $this->createMetaPropertyMetadata('testField', 'integer');
        $expected->setPropertyPath('propertyPath');

        $result = $this->objectMetadataFactory->createAndAddMetaPropertyMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getMetaProperty('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddFieldMetadata()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setIdentifierFieldNames(['id']);
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setPropertyPath('propertyPath');

        $expected = $this->createFieldMetadata('testField', 'integer');
        $expected->setPropertyPath('propertyPath');
        $expected->setIsNullable(true);

        $result = $this->objectMetadataFactory->createAndAddFieldMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getField('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddFieldMetadataForIdentifierField()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setIdentifierFieldNames(['testField']);
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setPropertyPath('propertyPath');

        $expected = $this->createFieldMetadata('testField', 'integer');
        $expected->setPropertyPath('propertyPath');

        $result = $this->objectMetadataFactory->createAndAddFieldMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getField('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadata()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setPropertyPath('propertyPath');
        $field->setTargetClass('Test\TargetClass');

        $expected = $this->createAssociationMetadata(
            'testField',
            'Test\TargetClass',
            'manyToOne',
            false,
            'integer',
            ['Test\TargetClass'],
            false
        );
        $expected->setPropertyPath('propertyPath');

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataForCollapsed()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setTargetClass('Test\TargetClass');
        $field->setCollapsed(true);

        $expected = $this->createAssociationMetadata(
            'testField',
            'Test\TargetClass',
            'manyToOne',
            false,
            'integer',
            ['Test\TargetClass'],
            true
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataForCollection()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setTargetClass('Test\TargetClass');
        $field->setTargetType('to-many');

        $expected = $this->createAssociationMetadata(
            'testField',
            'Test\TargetClass',
            'manyToMany',
            true,
            'integer',
            ['Test\TargetClass'],
            false
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataWithCustomTargetClass()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setTargetClass('Test\TargetClass');

        $expected = $this->createAssociationMetadata(
            'testField',
            'Test\AnotherClass',
            'manyToOne',
            false,
            'integer',
            ['Test\AnotherClass'],
            false
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test',
            'Test\AnotherClass'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataWithoutDataTypeAndWithoutTargetConfig()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setTargetClass('Test\TargetClass');

        $expected = $this->createAssociationMetadata(
            'testField',
            'Test\TargetClass',
            'manyToOne',
            false,
            null,
            ['Test\TargetClass'],
            false
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataWithoutDataTypeAndWhenNoIdInTargetConfig()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setTargetClass('Test\TargetClass');
        $field->createAndSetTargetEntity();

        $expected = $this->createAssociationMetadata(
            'testField',
            'Test\TargetClass',
            'manyToOne',
            false,
            'string',
            ['Test\TargetClass'],
            false
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataWithoutDataTypeAndCompositeTargetId()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setTargetClass('Test\TargetClass');
        $target = $field->createAndSetTargetEntity();
        $target->setIdentifierFieldNames(['id1', 'id2']);

        $expected = $this->createAssociationMetadata(
            'testField',
            'Test\TargetClass',
            'manyToOne',
            false,
            'string',
            ['Test\TargetClass'],
            false
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataWithoutDataTypeWithMissingTargetIdField()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setTargetClass('Test\TargetClass');
        $target = $field->createAndSetTargetEntity();
        $target->setIdentifierFieldNames(['id']);

        $expected = $this->createAssociationMetadata(
            'testField',
            'Test\TargetClass',
            'manyToOne',
            false,
            'string',
            ['Test\TargetClass'],
            false
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataWithoutDataTypeWhenTargetIdFieldHasDataType()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setTargetClass('Test\TargetClass');
        $target = $field->createAndSetTargetEntity();
        $target->setIdentifierFieldNames(['id']);
        $target->addField('id')->setDataType('integer');

        $expected = $this->createAssociationMetadata(
            'testField',
            'Test\TargetClass',
            'manyToOne',
            false,
            'integer',
            ['Test\TargetClass'],
            false
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataForToOneExtendedAssociation()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('association:manyToOne');
        $field->setTargetClass(EntityIdentifier::class);
        $target = $field->createAndSetTargetEntity();
        $target->setIdentifierFieldNames(['id']);
        $target->addField('id')->setDataType('integer');

        $this->associationManager->expects($this->once())
            ->method('getAssociationTargets')
            ->with('Test\Class', null, 'manyToOne', null)
            ->willReturn(['Test\Association1Target' => 'field1']);

        $expected = $this->createAssociationMetadata(
            'testField',
            EntityIdentifier::class,
            'manyToOne',
            false,
            'integer',
            ['Test\Association1Target'],
            false
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataForToManyExtendedAssociation()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('association:manyToMany');
        $field->setTargetClass(EntityIdentifier::class);
        $field->setTargetType('to-many');
        $target = $field->createAndSetTargetEntity();
        $target->setIdentifierFieldNames(['id']);
        $target->addField('id')->setDataType('integer');

        $this->associationManager->expects($this->once())
            ->method('getAssociationTargets')
            ->with('Test\Class', null, 'manyToMany', null)
            ->willReturn(['Test\Association1Target' => 'field1']);

        $expected = $this->createAssociationMetadata(
            'testField',
            EntityIdentifier::class,
            'manyToMany',
            true,
            'integer',
            ['Test\Association1Target'],
            false
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataForToManyInverseExtendedAssociation()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('inverseAssociation:Test\TargetClass:manyToOne:testKind');
        $field->setTargetClass(EntityIdentifier::class);
        $field->setTargetType('to-many');
        $target = $field->createAndSetTargetEntity();
        $target->setIdentifierFieldNames(['id']);
        $target->addField('id')->setDataType('integer');

        $this->associationManager->expects($this->once())
            ->method('getAssociationTargets')
            ->with('Test\TargetClass', null, 'manyToOne', 'testKind')
            ->willReturn(['Test\Class' => 'field1']);

        $this->fieldTypeHelper->expects($this->once())
            ->method('getUnderlyingType')
            ->with('manyToOne')
            ->willReturn('manyToOne');

        $expected = $this->createAssociationMetadata(
            'testField',
            'Test\TargetClass',
            'oneToMany',
            true,
            null,
            ['Test\TargetClass'],
            false
        );
        $expected->set('association-field', 'field1');

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }
}
