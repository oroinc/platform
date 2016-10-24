<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\MetadataHelper;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataBuilder;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;

class ObjectMetadataBuilderTest extends LoaderTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $associationManager;

    /** @var ObjectMetadataBuilder */
    protected $objectMetadataBuilder;

    protected function setUp()
    {
        $this->associationManager = $this->getMockBuilder(AssociationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectMetadataBuilder = new ObjectMetadataBuilder(
            new MetadataHelper(),
            $this->associationManager
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
            $this->objectMetadataBuilder->createObjectMetadata('Test\Class', $config)
        );
    }

    public function testAddMetaPropertyMetadata()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setPropertyPath('propertyPath');

        $expected = $this->createMetaPropertyMetadata('testField', 'integer');
        $expected->setPropertyPath('propertyPath');

        $result = $this->objectMetadataBuilder->addMetaPropertyMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getMetaProperty('testField'));
        self::assertEquals($expected, $result);
    }

    public function testAddFieldMetadata()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setIdentifierFieldNames(['id']);
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setPropertyPath('propertyPath');

        $expected = $this->createFieldMetadata('testField', 'integer');
        $expected->setPropertyPath('propertyPath');
        $expected->setIsNullable(true);

        $result = $this->objectMetadataBuilder->addFieldMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getField('testField'));
        self::assertEquals($expected, $result);
    }

    public function testAddFieldMetadataForIdentifierField()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setIdentifierFieldNames(['testField']);
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setPropertyPath('propertyPath');

        $expected = $this->createFieldMetadata('testField', 'integer');
        $expected->setPropertyPath('propertyPath');

        $result = $this->objectMetadataBuilder->addFieldMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getField('testField'));
        self::assertEquals($expected, $result);
    }

    public function testAddAssociationMetadata()
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

        $result = $this->objectMetadataBuilder->addAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testAddAssociationMetadataForCollapsed()
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

        $result = $this->objectMetadataBuilder->addAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testAddAssociationMetadataForCollection()
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

        $result = $this->objectMetadataBuilder->addAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testAddAssociationMetadataWithCustomTargetClass()
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

        $result = $this->objectMetadataBuilder->addAssociationMetadata(
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

    public function testAddAssociationMetadataWithoutDataTypeAndWithoutTargetConfig()
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

        $result = $this->objectMetadataBuilder->addAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testAddAssociationMetadataWithoutDataTypeAndWhenNoIdInTargetConfig()
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

        $result = $this->objectMetadataBuilder->addAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testAddAssociationMetadataWithoutDataTypeAndCompositeTargetId()
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

        $result = $this->objectMetadataBuilder->addAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testAddAssociationMetadataWithoutDataTypeWithMissingTargetIdField()
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

        $result = $this->objectMetadataBuilder->addAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testAddAssociationMetadataWithoutDataTypeWhenTargetIdFieldHasDataType()
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

        $result = $this->objectMetadataBuilder->addAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testAddAssociationMetadataForToOneExtendedAssociation()
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

        $result = $this->objectMetadataBuilder->addAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testAddAssociationMetadataForToManyExtendedAssociation()
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

        $result = $this->objectMetadataBuilder->addAssociationMetadata(
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
