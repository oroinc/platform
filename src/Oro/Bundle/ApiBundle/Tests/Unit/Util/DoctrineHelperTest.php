<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

class DoctrineHelperTest extends OrmRelatedTestCase
{
    const ENTITY_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';

    public function testIsManageableEntityClassShouldBeCached()
    {
        $entityClass = 'Test\Entity';
        $doctrine = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($this->em);

        $doctrineHelper = new DoctrineHelper($doctrine);
        $this->assertTrue($doctrineHelper->isManageableEntityClass($entityClass));
        // test local cache
        $this->assertTrue($doctrineHelper->isManageableEntityClass($entityClass));
    }

    public function testIsManageableEntityClassShouldBeCachedEvenForNotManageableEntity()
    {
        $entityClass = 'Test\Entity';
        $doctrine = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn(null);

        $doctrineHelper = new DoctrineHelper($doctrine);
        $this->assertFalse($doctrineHelper->isManageableEntityClass($entityClass));
        // test local cache
        $this->assertFalse($doctrineHelper->isManageableEntityClass($entityClass));
    }

    public function testApplyCriteriaWithoutJoins()
    {
        $qb = $this->getQueryBuilderMock();

        $criteria = new Criteria(new EntityClassResolver($this->doctrine));

        $qb->expects($this->once())
            ->method('addCriteria')
            ->with($this->identicalTo($criteria));

        $this->doctrineHelper->applyCriteria($qb, $criteria);
    }

    public function testApplyCriteria()
    {
        $qb = $this->getQueryBuilderMock();

        $criteria = new Criteria(new EntityClassResolver($this->doctrine));
        $criteria
            ->addInnerJoin(
                'category',
                $this->getEntityClass('Category')
            )
            ->setAlias('user_category');
        $criteria
            ->addLeftJoin(
                'products',
                $this->getEntityClass('Product'),
                Join::WITH,
                '{entity}.name IS NULL',
                'idx_name'
            )
            ->setAlias('products');
        $criteria
            ->addLeftJoin(
                'products.owner',
                $this->getEntityClass('User'),
                Join::WITH,
                '{entity}.name = {root}.name'
            )
            ->setAlias('product_owner');
        $criteria
            ->addLeftJoin(
                'products.category',
                '{products}.category',
                Join::WITH,
                '{entity}.name = {category}.name'
            )
            ->setAlias('product_category');

        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['user']);

        $qb->expects($this->at(1))
            ->method('innerJoin')
            ->with(
                $this->getEntityClass('Category'),
                'user_category',
                null,
                null,
                null
            );
        $qb->expects($this->at(2))
            ->method('leftJoin')
            ->with(
                $this->getEntityClass('Product'),
                'products',
                Join::WITH,
                'products.name IS NULL',
                'idx_name'
            );
        $qb->expects($this->at(3))
            ->method('leftJoin')
            ->with(
                $this->getEntityClass('User'),
                'product_owner',
                Join::WITH,
                'product_owner.name = user.name',
                null
            );
        $qb->expects($this->at(4))
            ->method('leftJoin')
            ->with(
                'products.category',
                'product_category',
                Join::WITH,
                'product_category.name = user_category.name',
                null
            );

        $qb->expects($this->once())
            ->method('addCriteria')
            ->with($this->identicalTo($criteria));

        $this->doctrineHelper->applyCriteria($qb, $criteria);
    }

    public function testFindEntityMetadataByPath()
    {
        $this->assertEquals(
            $this->getClassMetadata('Category'),
            $this->doctrineHelper->findEntityMetadataByPath(
                $this->getEntityClass('User'),
                ['category']
            )
        );
        $this->assertNull(
            $this->doctrineHelper->findEntityMetadataByPath(
                $this->getEntityClass('User'),
                ['name']
            )
        );
        $this->assertEquals(
            $this->getClassMetadata('Category'),
            $this->doctrineHelper->findEntityMetadataByPath(
                $this->getEntityClass('User'),
                ['products', 'category']
            )
        );
        $this->assertNull(
            $this->doctrineHelper->findEntityMetadataByPath(
                $this->getEntityClass('User'),
                ['products', 'category', 'name']
            )
        );
    }

    public function testFindEntityMetadataByPathForNotManageableEntity()
    {
        $className = 'Test\Class';

        $this->notManageableClassNames = [$className];

        $this->assertNull(
            $this->doctrineHelper->findEntityMetadataByPath($className, ['association'])
        );
    }

    public function testGetOrderByIdentifierForEntityWithSingleIdentifier()
    {
        $this->assertEquals(
            ['id' => 'ASC'],
            $this->doctrineHelper->getOrderByIdentifier($this->getEntityClass('User'))
        );
        $this->assertEquals(
            ['id' => 'DESC'],
            $this->doctrineHelper->getOrderByIdentifier($this->getEntityClass('User'), true)
        );
    }

    public function testGetOrderByIdentifierForEntityWithCompositeIdentifier()
    {
        $this->assertEquals(
            ['id' => 'ASC', 'title' => 'ASC'],
            $this->doctrineHelper->getOrderByIdentifier($this->getEntityClass('CompositeKeyEntity'))
        );
        $this->assertEquals(
            ['id' => 'DESC', 'title' => 'DESC'],
            $this->doctrineHelper->getOrderByIdentifier($this->getEntityClass('CompositeKeyEntity'), true)
        );
    }

    public function testGetIndexedFields()
    {
        $this->assertEquals(
            [
                'id'          => 'integer', // primary key
                'name'        => 'string', // unique constraint
                'description' => 'string', // index
            ],
            $this->doctrineHelper->getIndexedFields($this->getClassMetadata('Role'))
        );
    }

    public function testGetIndexedAssociations()
    {
        // category = ManyToOne
        // groups = ManyToMany (should be ignored)
        // products = OneToMany (should be ignored)
        $this->assertEquals(
            [
                'category' => 'string',
            ],
            $this->doctrineHelper->getIndexedAssociations($this->getClassMetadata('User'))
        );
    }

    public function testSetIdentifierForEntityWithSingleId()
    {
        $entityId = 123;
        $entity = new Entity\Group();

        $this->doctrineHelper->setEntityIdentifier($entity, $entityId);
        $this->assertEquals($entityId, $entity->getId());
    }

    public function testSetIdentifierForEntityWithCompositeId()
    {
        $entityId = ['id' => 123, 'title' => 'test'];
        $entity = new Entity\CompositeKeyEntity();

        $this->doctrineHelper->setEntityIdentifier($entity, $entityId);
        $this->assertEquals($entityId['id'], $entity->getId());
        $this->assertEquals($entityId['title'], $entity->getTitle());
    }

    public function testSetInvalidIdentifierForEntityWithCompositeId()
    {
        $entityId = 123;
        $entity = new Entity\CompositeKeyEntity();

        $this->setExpectedException(
            '\InvalidArgumentException',
            sprintf(
                'Unexpected identifier value "%s" for composite primary key of the entity "%s".',
                $entityId,
                get_class($entity)
            )
        );

        $this->doctrineHelper->setEntityIdentifier($entity, $entityId);
    }

    public function testSetIdentifierWithUndefinedFieldForEntityWithCompositeId()
    {
        $entityId = ['id' => 123, 'title1' => 'test'];
        $entity = new Entity\CompositeKeyEntity();

        $this->setExpectedException(
            '\InvalidArgumentException',
            sprintf(
                'The entity "%s" does not have the "title1" property.',
                get_class($entity)
            )
        );

        $this->doctrineHelper->setEntityIdentifier($entity, $entityId);
    }

    /**
     * @param string $entityShortClass
     *
     * @return ClassMetadata
     */
    protected function getClassMetadata($entityShortClass)
    {
        return $this->doctrineHelper->getEntityMetadataForClass(
            $this->getEntityClass($entityShortClass)
        );
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
}
