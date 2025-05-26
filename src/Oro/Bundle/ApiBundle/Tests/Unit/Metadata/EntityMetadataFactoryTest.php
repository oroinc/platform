<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityMetadataFactoryTest extends OrmRelatedTestCase
{
    private EntityMetadataFactory $metadataFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->metadataFactory = new EntityMetadataFactory($this->doctrineHelper);
    }

    public function testCreateEntityMetadata(): void
    {
        $expectedMetadata = new EntityMetadata(Entity\Product::class);
        $expectedMetadata->setIdentifierFieldNames(['id']);
        $expectedMetadata->setHasIdentifierGenerator(true);
        $expectedMetadata->setInheritedType(false);

        $metadata = $this->metadataFactory->createEntityMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class)
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateEntityMetadataForEntityWithCompositeIdentifier(): void
    {
        $expectedMetadata = new EntityMetadata(Entity\CompositeKeyEntity::class);
        $expectedMetadata->setIdentifierFieldNames(['id', 'title']);
        $expectedMetadata->setHasIdentifierGenerator(false);
        $expectedMetadata->setInheritedType(false);

        $metadata = $this->metadataFactory->createEntityMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\CompositeKeyEntity::class)
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateMetaPropertyMetadata(): void
    {
        $expectedMetadata = new MetaPropertyMetadata('name', 'string');

        $metadata = $this->metadataFactory->createMetaPropertyMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'name'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateMetaPropertyMetadataByPropertyPath(): void
    {
        $expectedMetadata = new MetaPropertyMetadata('id', 'integer');

        $metadata = $this->metadataFactory->createMetaPropertyMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'owner.id'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateMetaPropertyMetadataWhenDataTypeIsSpecified(): void
    {
        $expectedMetadata = new MetaPropertyMetadata('name', 'integer');

        $metadata = $this->metadataFactory->createMetaPropertyMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'name',
            'integer'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateMetaPropertyMetadataForNotManageableField(): void
    {
        $expectedMetadata = new MetaPropertyMetadata('unmanageableField');

        $metadata = $this->metadataFactory->createMetaPropertyMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'unmanageableField'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateFieldMetadataForIdentifier(): void
    {
        $expectedMetadata = new FieldMetadata();
        $expectedMetadata->setName('id');
        $expectedMetadata->setDataType('integer');
        $expectedMetadata->setIsNullable(false);

        $metadata = $this->metadataFactory->createFieldMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'id'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateFieldMetadata(): void
    {
        $expectedMetadata = new FieldMetadata();
        $expectedMetadata->setName('name');
        $expectedMetadata->setDataType('string');
        $expectedMetadata->setIsNullable(false);
        $expectedMetadata->setMaxLength(50);

        $metadata = $this->metadataFactory->createFieldMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'name'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateFieldMetadataByPropertyPath(): void
    {
        $expectedMetadata = new FieldMetadata();
        $expectedMetadata->setName('label');
        $expectedMetadata->setDataType('string');
        $expectedMetadata->setIsNullable(false);
        $expectedMetadata->setMaxLength(255);

        $metadata = $this->metadataFactory->createFieldMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'category.label'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateFieldMetadataWhenDataTypeIsSpecified(): void
    {
        $expectedMetadata = new FieldMetadata();
        $expectedMetadata->setName('name');
        $expectedMetadata->setDataType('integer');
        $expectedMetadata->setIsNullable(false);
        $expectedMetadata->setMaxLength(50);

        $metadata = $this->metadataFactory->createFieldMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'name',
            'integer'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateFieldMetadataForNullable(): void
    {
        $expectedMetadata = new FieldMetadata();
        $expectedMetadata->setName('updatedAt');
        $expectedMetadata->setDataType('datetime');
        $expectedMetadata->setIsNullable(true);

        $metadata = $this->metadataFactory->createFieldMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'updatedAt'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateFieldMetadataForNonManageableField(): void
    {
        $expectedMetadata = new FieldMetadata();
        $expectedMetadata->setName('another');
        $expectedMetadata->setDataType('string');
        $expectedMetadata->setIsNullable(true);

        $metadata = $this->metadataFactory->createFieldMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'another',
            'string'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateFieldMetadataForNonManageableFieldWhenFieldTypeIsNotSpecified(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'The data type for "%s::another" is not defined.',
            Entity\Product::class
        ));

        $this->metadataFactory->createFieldMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'another'
        );
    }

    public function testCreateAssociationMetadataForManyToOne(): void
    {
        $expectedMetadata = new AssociationMetadata();
        $expectedMetadata->setName('category');
        $expectedMetadata->setDataType('string');
        $expectedMetadata->setIsNullable(true);
        $expectedMetadata->setAssociationType('manyToOne');
        $expectedMetadata->setIsCollection(false);
        $expectedMetadata->setTargetClassName(Entity\Category::class);
        $expectedMetadata->setAcceptableTargetClassNames([Entity\Category::class]);

        $metadata = $this->metadataFactory->createAssociationMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'category'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateAssociationMetadataForManyToOneByPropertyPath(): void
    {
        $expectedMetadata = new AssociationMetadata();
        $expectedMetadata->setName('category');
        $expectedMetadata->setDataType('string');
        $expectedMetadata->setIsNullable(false);
        $expectedMetadata->setAssociationType('manyToOne');
        $expectedMetadata->setIsCollection(false);
        $expectedMetadata->setTargetClassName(Entity\Category::class);
        $expectedMetadata->setAcceptableTargetClassNames([Entity\Category::class]);

        $metadata = $this->metadataFactory->createAssociationMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'owner.category'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateAssociationMetadataForNotNullableManyToOne(): void
    {
        $expectedMetadata = new AssociationMetadata();
        $expectedMetadata->setName('category');
        $expectedMetadata->setDataType('string');
        $expectedMetadata->setIsNullable(false);
        $expectedMetadata->setAssociationType('manyToOne');
        $expectedMetadata->setIsCollection(false);
        $expectedMetadata->setTargetClassName(Entity\Category::class);
        $expectedMetadata->setAcceptableTargetClassNames([Entity\Category::class]);

        $metadata = $this->metadataFactory->createAssociationMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\User::class),
            'category'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateAssociationMetadataForManyToMany(): void
    {
        $expectedMetadata = new AssociationMetadata();
        $expectedMetadata->setName('users');
        $expectedMetadata->setDataType('integer');
        $expectedMetadata->setIsNullable(true);
        $expectedMetadata->setAssociationType('manyToMany');
        $expectedMetadata->setIsCollection(true);
        $expectedMetadata->setTargetClassName(Entity\User::class);
        $expectedMetadata->setAcceptableTargetClassNames([Entity\User::class]);

        $metadata = $this->metadataFactory->createAssociationMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Role::class),
            'users'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateAssociationMetadataForManyToManyByPropertyPath(): void
    {
        $expectedMetadata = new AssociationMetadata();
        $expectedMetadata->setName('groups');
        $expectedMetadata->setDataType('integer');
        $expectedMetadata->setIsNullable(true);
        $expectedMetadata->setAssociationType('manyToMany');
        $expectedMetadata->setIsCollection(true);
        $expectedMetadata->setTargetClassName(Entity\Group::class);
        $expectedMetadata->setAcceptableTargetClassNames([Entity\Group::class]);

        $metadata = $this->metadataFactory->createAssociationMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'owner.groups'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateAssociationMetadataWithDataType(): void
    {
        $expectedMetadata = new AssociationMetadata();
        $expectedMetadata->setName('category');
        $expectedMetadata->setDataType('integer');
        $expectedMetadata->setIsNullable(true);
        $expectedMetadata->setAssociationType('manyToOne');
        $expectedMetadata->setIsCollection(false);
        $expectedMetadata->setTargetClassName(Entity\Category::class);
        $expectedMetadata->setAcceptableTargetClassNames([Entity\Category::class]);

        $metadata = $this->metadataFactory->createAssociationMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'category',
            'integer'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }
}
