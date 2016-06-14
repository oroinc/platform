<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\AddParentEntityIdToQuery;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;

class AddParentEntityIdToQueryTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    const ENTITY_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';

    /** @var AddParentEntityIdToQuery */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new AddParentEntityIdToQuery();
    }

    /**
     * @param string $entityShortClass
     *
     * @return string
     */
    protected function getEntityClass($entityShortClass)
    {
        return self::ENTITY_NAMESPACE . $entityShortClass;
    }

    public function testProcessWhenQueryDoesNotExist()
    {
        $this->processor->process($this->context);
        $this->assertNull($this->context->getQuery());
    }

    public function testProcessForUnsupportedQuery()
    {
        $query = new \stdClass();

        $this->context->setQuery($query);
        $this->processor->process($this->context);
        $this->assertSame($query, $this->context->getQuery());
    }

    public function testProcessForQueryWithSeveralRootAliases()
    {
        $query = $this->doctrineHelper
            ->getEntityManagerForClass($this->getEntityClass('Product'))
            ->createQueryBuilder()
            ->from($this->getEntityClass('User'), 'root1')
            ->from($this->getEntityClass('Product'), 'root2');

        $this->context->setParentClassName($this->getEntityClass('User'));
        $this->context->setParentId(123);
        $this->context->setAssociationName('products');
        $this->context->setQuery($query);
        $this->processor->process($this->context);
        $this->assertEquals(
            'SELECT FROM '
            . 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User root1, '
            . 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product root2',
            $this->context->getQuery()->getDQL()
        );
    }

    public function testProcessForToManyAssociation()
    {
        $associationName = 'products';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName);

        $query = $this->doctrineHelper
            ->getEntityRepositoryForClass($this->getEntityClass('Product'))
            ->createQueryBuilder('e');

        $this->context->setIsCollection(true);
        $this->context->setParentClassName($this->getEntityClass('User'));
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        $this->assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product e'
            . ' INNER JOIN Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User parent_entity'
            . ' WITH e MEMBER OF parent_entity.products'
            . ' WHERE parent_entity = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        $this->assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForToManyRenamedAssociation()
    {
        $associationName = 'renamedProducts';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setPropertyPath('products');

        $query = $this->doctrineHelper
            ->getEntityRepositoryForClass($this->getEntityClass('Product'))
            ->createQueryBuilder('e');

        $this->context->setIsCollection(true);
        $this->context->setParentClassName($this->getEntityClass('User'));
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        $this->assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product e'
            . ' INNER JOIN Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User parent_entity'
            . ' WITH e MEMBER OF parent_entity.products'
            . ' WHERE parent_entity = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        $this->assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForToOneAssociation()
    {
        $associationName = 'owner';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName);

        $query = $this->doctrineHelper
            ->getEntityRepositoryForClass($this->getEntityClass('User'))
            ->createQueryBuilder('e');

        $this->context->setParentClassName($this->getEntityClass('Product'));
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        $this->assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e'
            . ' INNER JOIN Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product parent_entity'
            . ' WITH parent_entity.owner = e'
            . ' WHERE parent_entity = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        $this->assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }

    public function testProcessForToOneRenamedAssociation()
    {
        $associationName = 'renamedOwner';
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setPropertyPath('owner');

        $query = $this->doctrineHelper
            ->getEntityRepositoryForClass($this->getEntityClass('User'))
            ->createQueryBuilder('e');

        $this->context->setParentClassName($this->getEntityClass('Product'));
        $this->context->setParentId($parentId);
        $this->context->setAssociationName($associationName);
        $this->context->setParentConfig($parentConfig);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        $this->assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User e'
            . ' INNER JOIN Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product parent_entity'
            . ' WITH parent_entity.owner = e'
            . ' WHERE parent_entity = :parent_entity_id',
            $this->context->getQuery()->getDQL()
        );
        $this->assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('parent_entity_id')->getValue()
        );
    }
}
