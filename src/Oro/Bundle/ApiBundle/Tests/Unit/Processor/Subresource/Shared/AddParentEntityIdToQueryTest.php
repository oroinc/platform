<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\AddParentEntityIdToQuery;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AddParentEntityIdToQueryTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    /** @var AddParentEntityIdToQuery */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new AddParentEntityIdToQuery(
            $this->doctrineHelper,
            new EntityIdHelper()
        );
    }

    public function testProcessWhenQueryDoesNotExist()
    {
        $this->processor->process($this->context);
        self::assertNull($this->context->getQuery());
    }

    public function testProcessForUnsupportedQuery()
    {
        $query = new \stdClass();

        $this->context->setQuery($query);
        $this->processor->process($this->context);
        self::assertSame($query, $this->context->getQuery());
    }

    public function testProcessForQueryWithSeveralRootAliases()
    {
        $query = $this->doctrineHelper
            ->getEntityManagerForClass(Entity\Product::class)
            ->createQueryBuilder()
            ->from(Entity\User::class, 'root1')
            ->from(Entity\Product::class, 'root2');

        $this->context->setParentClassName(Entity\User::class);
        $this->context->setParentId(123);
        $this->context->setAssociationName('products');
        $this->context->setQuery($query);
        $this->processor->process($this->context);
        self::assertEquals(
            'SELECT FROM '
            . 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User root1, '
            . 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product root2',
            $this->context->getQuery()->getDQL()
        );
    }

    public function testProcessForSubresourceThatDoesNotAssociatedWithAnyFieldInParentEntityConfig()
    {
        $associationName = 'association';

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper
            ->getEntityRepositoryForClass(Entity\User::class)
            ->createQueryBuilder('e');

        $this->context->setParentClassName(Entity\Product::class);
        $this->context->setParentId(-1);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e',
            $this->context->getQuery()->getDQL()
        );
        self::assertCount(0, $this->context->getQuery()->getParameters());
    }

    public function testProcessForComputedAssociationWhenQueryForItIsPreparedByAnotherProcessor()
    {
        $associationName = 'owner';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentConfig->addField($associationName)
            ->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper
            ->getEntityRepositoryForClass(Entity\User::class)
            ->createQueryBuilder('e')
            ->where('e.owner = :parent_entity_id')
            ->setParameter('parent_entity_id', $parentId);

        $this->context->setParentClassName(Entity\Product::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e'
            . ' WHERE e.owner = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForComputedAssociationWhenQueryForItIsNotPreparedByAnotherProcessor()
    {
        $associationName = 'owner';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentConfig->addField($associationName)
            ->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper
            ->getEntityRepositoryForClass(Entity\User::class)
            ->createQueryBuilder('r')
            ->innerJoin('e.owner', 'e');

        $this->context->setParentClassName(Entity\Product::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT r'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User r'
            . ' INNER JOIN e.owner e'
            . ' WHERE e.id = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForComputedAssociationAndCompositeParentIdWhenQueryForItIsPreparedByAnotherProcessor()
    {
        $associationName = 'owner';
        $parentId = ['id' => 123, 'title' => 'test'];

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id', 'title']);
        $parentConfig->addField('id');
        $parentConfig->addField('title');
        $parentConfig->addField($associationName)
            ->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentMetadata->addField(new FieldMetadata('title'));

        $query = $this->doctrineHelper
            ->getEntityRepositoryForClass(Entity\User::class)
            ->createQueryBuilder('e')
            ->innerJoin('e.owner', 'o')
            ->where('o.id = :parent_entity_id1 AND o.name = :parent_entity_id2')
            ->setParameter('parent_entity_id1', $parentId['id'])
            ->setParameter('parent_entity_id2', $parentId['title']);

        $this->context->setParentClassName(Entity\Product::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e'
            . ' INNER JOIN e.owner o'
            . ' WHERE o.id = :parent_entity_id1 AND o.name = :parent_entity_id2',
            $this->context->getQuery()->getDQL()
        );
        self::assertSame(
            $parentId['id'],
            $this->context->getQuery()->getParameter('parent_entity_id1')->getValue()
        );
        self::assertSame(
            $parentId['title'],
            $this->context->getQuery()->getParameter('parent_entity_id2')->getValue()
        );
    }

    public function testProcessForComputedAssociationAndCompositeParentIdWhenQueryForItIsNotPreparedByAnotherProcessor()
    {
        $associationName = 'owner';
        $parentId = ['id' => 123, 'title' => 'test'];

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id', 'title']);
        $parentConfig->addField('id');
        $parentConfig->addField('title');
        $parentConfig->addField($associationName)
            ->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentMetadata->addField(new FieldMetadata('title'));

        $query = $this->doctrineHelper
            ->getEntityRepositoryForClass(Entity\User::class)
            ->createQueryBuilder('r')
            ->innerJoin('e.owner', 'e');

        $this->context->setParentClassName(Entity\Product::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT r'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User r'
            . ' INNER JOIN e.owner e'
            . ' WHERE e.id = :parent_entity_id1 AND e.title = :parent_entity_id2',
            $this->context->getQuery()->getDQL()
        );
        self::assertSame(
            $parentId['id'],
            $this->context->getQuery()->getParameter('parent_entity_id1')->getValue()
        );
        self::assertSame(
            $parentId['title'],
            $this->context->getQuery()->getParameter('parent_entity_id2')->getValue()
        );
    }

    public function testProcessForToManyBidirectionalAssociation()
    {
        $associationName = 'products';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentConfig->addField($associationName);
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper->createQueryBuilder(Entity\Product::class, 'e');

        $this->context->setIsCollection(true);
        $this->context->setParentClassName(Entity\User::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product e'
            . ' INNER JOIN e.owner parent_entity1'
            . ' WHERE parent_entity1.id = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForToManyUnidirectionalAssociation()
    {
        $associationName = 'users';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentConfig->addField($associationName);
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper->createQueryBuilder(Entity\User::class, 'e');

        $this->context->setIsCollection(true);
        $this->context->setParentClassName(Entity\Origin::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e'
            . ' INNER JOIN Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Origin parent_entity1'
            . ' WITH e MEMBER OF parent_entity1.users'
            . ' WHERE parent_entity1.id = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForToManyRenamedBidirectionalAssociation()
    {
        $associationName = 'renamedProducts';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentConfig->addField($associationName)->setPropertyPath('products');
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper->createQueryBuilder(Entity\Product::class, 'e');

        $this->context->setIsCollection(true);
        $this->context->setParentClassName(Entity\User::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product e'
            . ' INNER JOIN e.owner parent_entity1'
            . ' WHERE parent_entity1.id = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForToManyRenamedUnidirectionalAssociation()
    {
        $associationName = 'renamedUsers';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentConfig->addField($associationName)->setPropertyPath('users');
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper->createQueryBuilder(Entity\User::class, 'e');

        $this->context->setIsCollection(true);
        $this->context->setParentClassName(Entity\Origin::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e'
            . ' INNER JOIN Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Origin parent_entity1'
            . ' WITH e MEMBER OF parent_entity1.users'
            . ' WHERE parent_entity1.id = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForToOneBidirectionalAssociation()
    {
        $associationName = 'owner';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentConfig->addField($associationName);
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper->createQueryBuilder(Entity\User::class, 'e');

        $this->context->setParentClassName(Entity\Product::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e'
            . ' INNER JOIN e.products parent_entity1'
            . ' WHERE parent_entity1.id = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForToOneUnidirectionalAssociation()
    {
        $associationName = 'user';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentConfig->addField($associationName);
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper->createQueryBuilder(Entity\User::class, 'e');

        $this->context->setParentClassName(Entity\Origin::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e'
            . ' INNER JOIN Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Origin parent_entity1'
            . ' WITH parent_entity1.user = e'
            . ' WHERE parent_entity1.id = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForToOneRenamedBidirectionalAssociation()
    {
        $associationName = 'renamedOwner';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentConfig->addField($associationName)->setPropertyPath('owner');
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper->createQueryBuilder(Entity\User::class, 'e');

        $this->context->setParentClassName(Entity\Product::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e'
            . ' INNER JOIN e.products parent_entity1'
            . ' WHERE parent_entity1.id = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForToOneRenamedUnidirectionalAssociation()
    {
        $associationName = 'renamedUser';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentConfig->addField($associationName)->setPropertyPath('user');
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper->createQueryBuilder(Entity\User::class, 'e');

        $this->context->setParentClassName(Entity\Origin::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e'
            . ' INNER JOIN Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Origin parent_entity1'
            . ' WITH parent_entity1.user = e'
            . ' WHERE parent_entity1.id = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForToOneInverseSideBidirectionalAssociation()
    {
        $associationName = 'origin';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentConfig->addField($associationName);
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper->createQueryBuilder(Entity\Origin::class, 'e');

        $this->context->setParentClassName(Entity\Mailbox::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Origin e'
            . ' INNER JOIN e.mailbox parent_entity1'
            . ' WHERE parent_entity1.id = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForToOneRenamedInverseSideBidirectionalAssociation()
    {
        $associationName = 'renamedOrigin';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentConfig->addField($associationName)->setPropertyPath('origin');
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper->createQueryBuilder(Entity\Origin::class, 'e');

        $this->context->setParentClassName(Entity\Mailbox::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Origin e'
            . ' INNER JOIN e.mailbox parent_entity1'
            . ' WHERE parent_entity1.id = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForRenamedParentIdentifierField()
    {
        $associationName = 'owner';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['renamedId']);
        $parentConfig->addField('renamedId')->setPropertyPath('id');
        $parentConfig->addField($associationName);
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('renamedId'))->setPropertyPath('id');

        $query = $this->doctrineHelper->createQueryBuilder(Entity\User::class, 'e');

        $this->context->setParentClassName(Entity\Product::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e'
            . ' INNER JOIN e.products parent_entity1'
            . ' WHERE parent_entity1.id = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForCompositeParentIdentifier()
    {
        $associationName = 'children';
        $parentId = ['id' => 123, 'title' => 'test'];

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id', 'title']);
        $parentConfig->addField('id');
        $parentConfig->addField('title');
        $parentConfig->addField($associationName);
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));
        $parentMetadata->addField(new FieldMetadata('title'));

        $query = $this->doctrineHelper->createQueryBuilder(Entity\CompositeKeyEntity::class, 'e');

        $this->context->setParentClassName(Entity\CompositeKeyEntity::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity e'
            . ' INNER JOIN Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity parent_entity1'
            . ' WITH parent_entity1.children = e'
            . ' WHERE parent_entity1.id = :parent_entity_id1 AND parent_entity1.title = :parent_entity_id2',
            $this->context->getQuery()->getDQL()
        );
        self::assertSame(
            $parentId['id'],
            $this->context->getQuery()->getParameter('parent_entity_id1')->getValue()
        );
        self::assertSame(
            $parentId['title'],
            $this->context->getQuery()->getParameter('parent_entity_id2')->getValue()
        );
    }

    public function testProcessForRenamedCompositeParentIdentifier()
    {
        $associationName = 'children';
        $parentId = ['renamedId' => 123, 'renamedTitle' => 'test'];

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['renamedId', 'renamedTitle']);
        $parentConfig->addField('renamedId')->setPropertyPath('id');
        $parentConfig->addField('renamedTitle')->setPropertyPath('title');
        $parentConfig->addField($associationName);
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('renamedId'))->setPropertyPath('id');
        $parentMetadata->addField(new FieldMetadata('renamedTitle'))->setPropertyPath('title');

        $query = $this->doctrineHelper->createQueryBuilder(Entity\CompositeKeyEntity::class, 'e');

        $this->context->setParentClassName(Entity\CompositeKeyEntity::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity e'
            . ' INNER JOIN Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity parent_entity1'
            . ' WITH parent_entity1.children = e'
            . ' WHERE parent_entity1.id = :parent_entity_id1 AND parent_entity1.title = :parent_entity_id2',
            $this->context->getQuery()->getDQL()
        );
        self::assertSame(
            $parentId['renamedId'],
            $this->context->getQuery()->getParameter('parent_entity_id1')->getValue()
        );
        self::assertSame(
            $parentId['renamedTitle'],
            $this->context->getQuery()->getParameter('parent_entity_id2')->getValue()
        );
    }

    public function testProcessForAssociationWithDeepPropertyPath()
    {
        $associationName = 'category';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentConfig->addField($associationName)->setPropertyPath('owner.category');
        $ownerFieldConfig = $parentConfig->addField('owner');
        $ownerFieldConfig->setTargetClass(Entity\Category::class);
        $ownerTargetConfig = $ownerFieldConfig->createAndSetTargetEntity();
        $ownerTargetConfig->addField('category');
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper->createQueryBuilder(Entity\User::class, 'e');

        $this->context->setParentClassName(Entity\Product::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e'
            . ' INNER JOIN Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category parent_entity1'
            . ' WITH parent_entity1.category = e'
            . ' INNER JOIN e.products parent_entity2'
            . ' WHERE parent_entity2.id = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForApiResourceBasedOnManageableEntity()
    {
        $this->notManageableClassNames[] = Entity\UserProfile::class;

        $associationName = 'category';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->setParentResourceClass(Entity\User::class);
        $parentConfig->setIdentifierFieldNames(['id']);
        $parentConfig->addField('id');
        $parentConfig->addField($associationName);
        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->setIdentifierFieldNames($parentConfig->getIdentifierFieldNames());
        $parentMetadata->addField(new FieldMetadata('id'));

        $query = $this->doctrineHelper->createQueryBuilder(Entity\Category::class, 'e');

        $this->context->setParentClassName(Entity\UserProfile::class);
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category e'
            . ' INNER JOIN Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User parent_entity1'
            . ' WITH parent_entity1.category = e'
            . ' WHERE parent_entity1.id = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        self::assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }
}
