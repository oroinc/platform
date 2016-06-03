<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

class EntityMetadataFactoryTest extends OrmRelatedTestCase
{
    const ENTITY_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';

    /** @var EntityMetadataFactory */
    protected $metadataFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->metadataFactory = new EntityMetadataFactory($this->doctrineHelper);
    }

    public function testCreateEntityMetadata()
    {
        $expectedMetadata = new EntityMetadata();
        $expectedMetadata->setClassName(self::ENTITY_NAMESPACE . 'Product');
        $expectedMetadata->setIdentifierFieldNames(['id']);
        $expectedMetadata->setHasIdentifierGenerator(true);
        $expectedMetadata->setInheritedType(false);

        $metadata = $this->metadataFactory->createEntityMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(self::ENTITY_NAMESPACE . 'Product')
        );

        $this->assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateEntityMetadataForEntityWithCompositeIdentifier()
    {
        $expectedMetadata = new EntityMetadata();
        $expectedMetadata->setClassName(self::ENTITY_NAMESPACE . 'CompositeKeyEntity');
        $expectedMetadata->setIdentifierFieldNames(['id', 'title']);
        $expectedMetadata->setHasIdentifierGenerator(false);
        $expectedMetadata->setInheritedType(false);

        $metadata = $this->metadataFactory->createEntityMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(self::ENTITY_NAMESPACE . 'CompositeKeyEntity')
        );

        $this->assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateFieldMetadataForIdentifier()
    {
        $expectedMetadata = new FieldMetadata();
        $expectedMetadata->setName('id');
        $expectedMetadata->setDataType('integer');
        $expectedMetadata->setIsNullable(false);

        $metadata = $this->metadataFactory->createFieldMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(self::ENTITY_NAMESPACE . 'Product'),
            'id'
        );

        $this->assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateFieldMetadataForString()
    {
        $expectedMetadata = new FieldMetadata();
        $expectedMetadata->setName('name');
        $expectedMetadata->setDataType('string');
        $expectedMetadata->setIsNullable(false);
        $expectedMetadata->setMaxLength(50);

        $metadata = $this->metadataFactory->createFieldMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(self::ENTITY_NAMESPACE . 'Product'),
            'name'
        );

        $this->assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateFieldMetadataForNullable()
    {
        $expectedMetadata = new FieldMetadata();
        $expectedMetadata->setName('updatedAt');
        $expectedMetadata->setDataType('datetime');
        $expectedMetadata->setIsNullable(true);

        $metadata = $this->metadataFactory->createFieldMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(self::ENTITY_NAMESPACE . 'Product'),
            'updatedAt'
        );

        $this->assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateAssociationMetadataForManyToOne()
    {
        $expectedMetadata = new AssociationMetadata();
        $expectedMetadata->setName('category');
        $expectedMetadata->setDataType('string');
        $expectedMetadata->setIsNullable(true);
        $expectedMetadata->setIsCollection(false);
        $expectedMetadata->setTargetClassName(self::ENTITY_NAMESPACE . 'Category');
        $expectedMetadata->setAcceptableTargetClassNames([self::ENTITY_NAMESPACE . 'Category']);

        $metadata = $this->metadataFactory->createAssociationMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(self::ENTITY_NAMESPACE . 'Product'),
            'category'
        );

        $this->assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateAssociationMetadataForNotNullableManyToOne()
    {
        $expectedMetadata = new AssociationMetadata();
        $expectedMetadata->setName('category');
        $expectedMetadata->setDataType('string');
        $expectedMetadata->setIsNullable(false);
        $expectedMetadata->setIsCollection(false);
        $expectedMetadata->setTargetClassName(self::ENTITY_NAMESPACE . 'Category');
        $expectedMetadata->setAcceptableTargetClassNames([self::ENTITY_NAMESPACE . 'Category']);

        $metadata = $this->metadataFactory->createAssociationMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(self::ENTITY_NAMESPACE . 'User'),
            'category'
        );

        $this->assertEquals($expectedMetadata, $metadata);
    }

    public function testCreateAssociationMetadataForManyToMany()
    {
        $expectedMetadata = new AssociationMetadata();
        $expectedMetadata->setName('users');
        $expectedMetadata->setDataType('integer');
        $expectedMetadata->setIsNullable(true);
        $expectedMetadata->setIsCollection(true);
        $expectedMetadata->setTargetClassName(self::ENTITY_NAMESPACE . 'User');
        $expectedMetadata->setAcceptableTargetClassNames([self::ENTITY_NAMESPACE . 'User']);

        $metadata = $this->metadataFactory->createAssociationMetadata(
            $this->doctrineHelper->getEntityMetadataForClass(self::ENTITY_NAMESPACE . 'Role'),
            'users'
        );

        $this->assertEquals($expectedMetadata, $metadata);
    }
}
