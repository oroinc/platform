<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\MetadataHelper;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataFactory;
use Oro\Bundle\ApiBundle\Provider\ExtendedAssociationProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ObjectMetadataFactoryTest extends LoaderTestCase
{
    /** @var ExtendedAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendedAssociationProvider;

    /** @var ObjectMetadataFactory */
    private $objectMetadataFactory;

    protected function setUp(): void
    {
        $this->extendedAssociationProvider = $this->createMock(ExtendedAssociationProvider::class);

        $this->objectMetadataFactory = new ObjectMetadataFactory(
            new MetadataHelper(),
            $this->extendedAssociationProvider
        );
    }

    public function testCreateObjectMetadata()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);

        $expected = new EntityMetadata('Test\Class');
        $expected->setIdentifierFieldNames(['id']);

        self::assertEquals(
            $expected,
            $this->objectMetadataFactory->createObjectMetadata('Test\Class', $config)
        );
    }

    public function testCreateObjectMetadataForMultiTarget()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);

        $expected = new EntityMetadata(EntityIdentifier::class);
        $expected->setIdentifierFieldNames(['id']);
        $expected->setInheritedType(true);

        self::assertEquals(
            $expected,
            $this->objectMetadataFactory->createObjectMetadata(EntityIdentifier::class, $config)
        );
    }

    public function testCreateAndAddMetaPropertyMetadata()
    {
        $entityMetadata = new EntityMetadata('Test\Entity');
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

    public function testCreateAndAddMetaPropertyMetadataWhenResultNameExistsInConfig()
    {
        $entityMetadata = new EntityMetadata('Test\Entity');
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('string');
        $field->setMetaPropertyResultName('resultName');

        $expected = $this->createMetaPropertyMetadata('testField', 'string');
        $expected->setResultName('resultName');

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
        $entityMetadata = new EntityMetadata('Test\Entity');
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
        $entityMetadata = new EntityMetadata('Test\Entity');
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
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
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
            ['Test\TargetClass']
        );
        $expected->setPropertyPath('propertyPath');

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            $config,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataForCollapsed()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
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
            $config,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataForCollection()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
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
            ['Test\TargetClass']
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            $config,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataWithCustomTargetClass()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('integer');
        $field->setTargetClass('Test\TargetClass');

        $expected = $this->createAssociationMetadata(
            'testField',
            'Test\AnotherClass',
            'manyToOne',
            false,
            'integer',
            ['Test\AnotherClass']
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            $config,
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
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
        $field = new EntityDefinitionFieldConfig();
        $field->setTargetClass('Test\TargetClass');

        $expected = $this->createAssociationMetadata(
            'testField',
            'Test\TargetClass',
            'manyToOne',
            false,
            null,
            ['Test\TargetClass']
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            $config,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataWithoutDataTypeAndWhenNoIdInTargetConfig()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
        $field = new EntityDefinitionFieldConfig();
        $field->setTargetClass('Test\TargetClass');
        $field->createAndSetTargetEntity();

        $expected = $this->createAssociationMetadata(
            'testField',
            'Test\TargetClass',
            'manyToOne',
            false,
            'string',
            ['Test\TargetClass']
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            $config,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataWithoutDataTypeAndCompositeTargetId()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
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
            ['Test\TargetClass']
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            $config,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataWithoutDataTypeWithMissingTargetIdField()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
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
            ['Test\TargetClass']
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            $config,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataWithoutDataTypeWhenTargetIdFieldHasDataType()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
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
            ['Test\TargetClass']
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            $config,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataForToOneExtendedAssociation()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('association:manyToOne');
        $field->setDependsOn(['field1']);
        $field->setTargetClass(EntityIdentifier::class);
        $target = $field->createAndSetTargetEntity();
        $target->setIdentifierFieldNames(['id']);
        $target->addField('id')->setDataType('integer');

        $this->extendedAssociationProvider->expects(self::once())
            ->method('filterExtendedAssociationTargets')
            ->with('Test\Class', 'manyToOne', null, ['field1'])
            ->willReturn(['Test\Association1Target' => 'field1']);

        $expected = $this->createAssociationMetadata(
            'testField',
            EntityIdentifier::class,
            'manyToOne',
            false,
            'integer',
            ['Test\Association1Target']
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            $config,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataForToManyExtendedAssociation()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('association:manyToMany');
        $field->setDependsOn(['field1']);
        $field->setTargetClass(EntityIdentifier::class);
        $field->setTargetType('to-many');
        $target = $field->createAndSetTargetEntity();
        $target->setIdentifierFieldNames(['id']);
        $target->addField('id')->setDataType('integer');

        $this->extendedAssociationProvider->expects(self::once())
            ->method('filterExtendedAssociationTargets')
            ->with('Test\Class', 'manyToMany', null, ['field1'])
            ->willReturn(['Test\Association1Target' => 'field1']);

        $expected = $this->createAssociationMetadata(
            'testField',
            EntityIdentifier::class,
            'manyToMany',
            true,
            'integer',
            ['Test\Association1Target']
        );

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            $config,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataForExtendedAssociationWithEmptyTargets()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('association:manyToOne');
        $field->setDependsOn(['field1']);
        $field->setTargetClass(EntityIdentifier::class);
        $target = $field->createAndSetTargetEntity();
        $target->setIdentifierFieldNames(['id']);
        $target->addField('id')->setDataType('integer');

        $this->extendedAssociationProvider->expects(self::once())
            ->method('filterExtendedAssociationTargets')
            ->with('Test\Class', 'manyToOne', null, ['field1'])
            ->willReturn([]);

        $expected = $this->createAssociationMetadata(
            'testField',
            EntityIdentifier::class,
            'manyToOne',
            false,
            'integer',
            []
        );
        $expected->setEmptyAcceptableTargetsAllowed(false);

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            $config,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }

    public function testCreateAndAddAssociationMetadataForExtendedAssociationWhenAllTargetsAreNotAccessibleViaApi()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata('Test\Entity');
        $field = new EntityDefinitionFieldConfig();
        $field->setDataType('association:manyToOne');
        $field->setTargetClass(EntityIdentifier::class);
        $target = $field->createAndSetTargetEntity();
        $target->setIdentifierFieldNames(['id']);
        $target->addField('id')->setDataType('integer');

        $this->extendedAssociationProvider->expects(self::never())
            ->method('filterExtendedAssociationTargets');

        $expected = $this->createAssociationMetadata(
            'testField',
            EntityIdentifier::class,
            'manyToOne',
            false,
            'integer',
            []
        );
        $expected->setEmptyAcceptableTargetsAllowed(false);

        $result = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            'Test\Class',
            $config,
            'testField',
            $field,
            'test'
        );
        self::assertSame($result, $entityMetadata->getAssociation('testField'));
        self::assertEquals($expected, $result);
    }
}
