<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

class EntityMetadataFactoryTest extends OrmRelatedTestCase
{
    /** @var EntityMetadataFactory */
    private $metadataFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->metadataFactory = new EntityMetadataFactory($this->doctrineHelper);
    }

    public function testCreateEntityMetadata()
    {
        $expectedMetadata = new EntityMetadata();
        $expectedMetadata->setClassName(Entity\Product::class);
        $expectedMetadata->setIdentifierFieldNames(['id']);
        $expectedMetadata->setHasIdentifierGenerator(true);
        $expectedMetadata->setInheritedType(false);

        $metadata = $this->metadataFactory->createEntityMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class)
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateEntityMetadataForEntityWithCompositeIdentifier()
    {
        $expectedMetadata = new EntityMetadata();
        $expectedMetadata->setClassName(Entity\CompositeKeyEntity::class);
        $expectedMetadata->setIdentifierFieldNames(['id', 'title']);
        $expectedMetadata->setHasIdentifierGenerator(false);
        $expectedMetadata->setInheritedType(false);

        $metadata = $this->metadataFactory->createEntityMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\CompositeKeyEntity::class)
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateMetaPropertyMetadata()
    {
        $expectedMetadata = new MetaPropertyMetadata();
        $expectedMetadata->setName('name');
        $expectedMetadata->setDataType('string');

        $metadata = $this->metadataFactory->createMetaPropertyMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'name'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateMetaPropertyMetadataByPropertyPath()
    {
        $expectedMetadata = new MetaPropertyMetadata();
        $expectedMetadata->setName('id');
        $expectedMetadata->setDataType('integer');

        $metadata = $this->metadataFactory->createMetaPropertyMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'owner.id'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateMetaPropertyMetadataWhenDataTypeIsSpecified()
    {
        $expectedMetadata = new MetaPropertyMetadata();
        $expectedMetadata->setName('name');
        $expectedMetadata->setDataType('integer');

        $metadata = $this->metadataFactory->createMetaPropertyMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'name',
            'integer'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateMetaPropertyMetadataForNotManageableField()
    {
        $expectedMetadata = new MetaPropertyMetadata();
        $expectedMetadata->setName('unmanageableField');

        $metadata = $this->metadataFactory->createMetaPropertyMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(Entity\Product::class),
            'unmanageableField'
        );

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateFieldMetadataForIdentifier()
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

    public function testCreateFieldMetadata()
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

    public function testCreateFieldMetadataByPropertyPath()
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

    public function testCreateFieldMetadataWhenDataTypeIsSpecified()
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

    public function testCreateFieldMetadataForNullable()
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

    public function testCreateAssociationMetadataForManyToOne()
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

    public function testCreateAssociationMetadataForManyToOneByPropertyPath()
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

    public function testCreateAssociationMetadataForNotNullableManyToOne()
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

    public function testCreateAssociationMetadataForManyToMany()
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

    public function testCreateAssociationMetadataForManyToManyByPropertyPath()
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

    public function testCreateAssociationMetadataWithDataType()
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
