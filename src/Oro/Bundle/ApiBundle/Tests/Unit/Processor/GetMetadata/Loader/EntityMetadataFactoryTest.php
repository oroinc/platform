<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory as MetadataFactory;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\MetadataHelper;

class EntityMetadataFactoryTest extends LoaderTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataFactory */
    private $metadataFactory;

    /** @var EntityMetadataFactory */
    private $entityMetadataFactory;

    protected function setUp()
    {
        $this->metadataFactory = $this->createMock(MetadataFactory::class);

        $this->entityMetadataFactory = new EntityMetadataFactory(
            new MetadataHelper(),
            $this->metadataFactory
        );
    }

    public function testCreateAndAddMetaPropertyMetadata()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setPropertyPath('propertyPath');

        $classMetadata = $this->getClassMetadataMock('Test\Class');

        $metadata = new MetaPropertyMetadata('propertyPath');
        $metadata->setDataType('integer');

        $this->metadataFactory->expects(self::once())
            ->method('createMetaPropertyMetadata')
            ->with(self::identicalTo($classMetadata), 'propertyPath', 'integer')
            ->willReturn($metadata);

        $expected = $this->createMetaPropertyMetadata('testField', 'integer');
        $expected->setPropertyPath('propertyPath');

        $result = $this->entityMetadataFactory->createAndAddMetaPropertyMetadata(
            $entityMetadata,
            $classMetadata,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getMetaProperty('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddMetaPropertyMetadataWhenResultNameExistsInConfig()
    {
        $entityMetadata = new EntityMetadata();
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('string');
        $field->setMetaPropertyResultName('resultName');

        $classMetadata = $this->getClassMetadataMock('Test\Class');

        $metadata = new MetaPropertyMetadata('testField');
        $metadata->setDataType('string');

        $this->metadataFactory->expects(self::once())
            ->method('createMetaPropertyMetadata')
            ->with(self::identicalTo($classMetadata), 'testField', 'string')
            ->willReturn($metadata);

        $expected = $this->createMetaPropertyMetadata('testField', 'string');
        $expected->setResultName('resultName');

        $result = $this->entityMetadataFactory->createAndAddMetaPropertyMetadata(
            $entityMetadata,
            $classMetadata,
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

        $classMetadata = $this->getClassMetadataMock('Test\Class');

        $metadata = new FieldMetadata('propertyPath');
        $metadata->setDataType('integer');

        $this->metadataFactory->expects(self::once())
            ->method('createFieldMetadata')
            ->with(self::identicalTo($classMetadata), 'propertyPath', 'integer')
            ->willReturn($metadata);

        $expected = $this->createFieldMetadata('testField', 'integer');
        $expected->setPropertyPath('propertyPath');

        $result = $this->entityMetadataFactory->createAndAddFieldMetadata(
            $entityMetadata,
            $classMetadata,
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
        $field->setCollapsed(true);

        $classMetadata = $this->getClassMetadataMock('Test\Class');

        $metadata = new AssociationMetadata('propertyPath');
        $metadata->setDataType('integer');
        $metadata->setTargetClassName('Test\TargetClass');
        $metadata->addAcceptableTargetClassName('Test\TargetClass');
        $metadata->setAssociationType('manyToOne');
        $metadata->setIsCollection(false);
        $metadata->setIsNullable(true);

        $this->metadataFactory->expects(self::once())
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

        $result = $this->entityMetadataFactory->createAndAddAssociationMetadata(
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
