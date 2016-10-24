<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityMetadataBuilder;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\MetadataHelper;

class EntityMetadataBuilderTest extends LoaderTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityMetadataFactory;

    /** @var EntityMetadataBuilder */
    protected $entityMetadataBuilder;

    protected function setUp()
    {
        $this->entityMetadataFactory = $this->getMockBuilder(EntityMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityMetadataBuilder = new EntityMetadataBuilder(
            new MetadataHelper(),
            $this->entityMetadataFactory
        );
    }

    public function testAddEntityMetaPropertyMetadata()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setPropertyPath('propertyPath');

        $classMetadata = $this->getClassMetadataMock('Test\Class');

        $metadata = new MetaPropertyMetadata('propertyPath');
        $metadata->setDataType('integer');

        $this->entityMetadataFactory->expects(self::once())
            ->method('createMetaPropertyMetadata')
            ->with(self::identicalTo($classMetadata), 'propertyPath', 'integer')
            ->willReturn($metadata);

        $expected = $this->createMetaPropertyMetadata('testField', 'integer');
        $expected->setPropertyPath('propertyPath');

        $result = $this->entityMetadataBuilder->addEntityMetaPropertyMetadata(
            $entityMetadata,
            $classMetadata,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getMetaProperty('testField'));
        self::assertEquals($expected, $result);
    }

    public function testAddEntityFieldMetadata()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setIdentifierFieldNames(['id']);
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setPropertyPath('propertyPath');

        $classMetadata = $this->getClassMetadataMock('Test\Class');

        $metadata = new FieldMetadata('propertyPath');
        $metadata->setDataType('integer');

        $this->entityMetadataFactory->expects(self::once())
            ->method('createFieldMetadata')
            ->with(self::identicalTo($classMetadata), 'propertyPath', 'integer')
            ->willReturn($metadata);

        $expected = $this->createFieldMetadata('testField', 'integer');
        $expected->setPropertyPath('propertyPath');

        $result = $this->entityMetadataBuilder->addEntityFieldMetadata(
            $entityMetadata,
            $classMetadata,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getField('testField'));
        self::assertEquals($expected, $result);
    }

    public function testAddEntityAssociationMetadata()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setPropertyPath('propertyPath');
        $field->setCollapsed(true);

        $classMetadata = $this->getClassMetadataMock('Test\Class');

        $metadata = new AssociationMetadata('propertyPath');
        $metadata->setDataType('integer');
        $metadata->setTargetClassName('Test\TargetClass');
        $metadata->addAcceptableTargetClassName('Test\TargetClass');
        $metadata->setAssociationType('manyToOne');
        $metadata->setIsCollection(false);
        $metadata->setIsNullable(true);

        $this->entityMetadataFactory->expects(self::once())
            ->method('createAssociationMetadata')
            ->with(self::identicalTo($classMetadata), 'propertyPath', 'integer')
            ->willReturn($metadata);

        $expected = $this->createAssociationMetadata(
            'testField',
            'Test\TargetClass',
            'manyToOne',
            false,
            'integer',
            ['Test\TargetClass'],
            true
        );
        $expected->setPropertyPath('propertyPath');

        $result = $this->entityMetadataBuilder->addEntityAssociationMetadata(
            $entityMetadata,
            $classMetadata,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }
}
