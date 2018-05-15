<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitorFactory;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\EqComparisonExpression;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\OrCompositeExpression;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\ApiBundle\Util\CriteriaNormalizer;
use Oro\Bundle\ApiBundle\Util\CriteriaPlaceholdersResolver;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class CriteriaConnectorTest extends OrmRelatedTestCase
{
    private const ENTITY_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';

    /** @var Criteria */
    private $criteria;

    /** @var CriteriaConnector */
    private $criteriaConnector;

    /** @var QueryExpressionVisitorFactory */
    private $expressionVisitorFactory;

    protected function setUp()
    {
        parent::setUp();

        $entityClassResolver = new EntityClassResolver($this->doctrine);
        $this->criteria = new Criteria($entityClassResolver);
        $this->expressionVisitorFactory = new QueryExpressionVisitorFactory(
            ['OR' => new OrCompositeExpression()],
            ['=' => new EqComparisonExpression()]
        );
        $this->criteriaConnector = new CriteriaConnector(
            new CriteriaNormalizer($this->doctrineHelper),
            new CriteriaPlaceholdersResolver(),
            $this->expressionVisitorFactory,
            $entityClassResolver
        );
    }

    /**
     * @param $expectedDql
     */
    private function assertQuery($expectedDql)
    {
        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\User::class, 'e');

        $this->criteriaConnector->applyCriteria($qb, $this->criteria);

        self::assertEquals(
            $expectedDql,
            str_replace(self::ENTITY_NAMESPACE, 'Test:', $qb->getDQL())
        );
    }

    public function testOrderBy()
    {
        $this->criteria->orderBy(['id' => Criteria::ASC, 'category.name' => Criteria::ASC]);
        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' ORDER BY e.id ASC, category.name ASC'
        );
    }

    public function testOrderByAssociation()
    {
        $this->criteria->orderBy(['id' => Criteria::ASC, 'category' => Criteria::ASC]);
        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' ORDER BY e.id ASC, e.category ASC'
        );
    }

    public function testWhere()
    {
        $this->criteria->andWhere(
            $this->criteria::expr()->orX(
                $this->criteria::expr()->eq('category.name', 'test_category'),
                $this->criteria::expr()->eq('groups.name', 'test_group')
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE category.name = :category_name OR groups.name = :groups_name'
        );
    }

    public function testWhereByAssociation()
    {
        $this->criteria->andWhere(
            $this->criteria::expr()->orX(
                $this->criteria::expr()->eq('category', 'test_category'),
                $this->criteria::expr()->eq('groups', 'test_group')
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE e.category = :category OR e.groups = :groups'
        );
    }

    public function testNestedFieldInOrderBy()
    {
        $this->criteria->orderBy(['id' => Criteria::ASC, 'products.category.name' => Criteria::ASC]);

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.products products'
            . ' LEFT JOIN products.category category'
            . ' ORDER BY e.id ASC, category.name ASC'
        );
    }

    public function testNestedAssociationInOrderBy()
    {
        $this->criteria->orderBy(['id' => Criteria::ASC, 'products.category' => Criteria::ASC]);

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.products products'
            . ' LEFT JOIN products.category category'
            . ' ORDER BY e.id ASC, products.category ASC'
        );
    }

    public function testNestedFieldInWhere()
    {
        $this->criteria->andWhere(
            $this->criteria::expr()->eq('products.category.name', 'test_category')
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.products products'
            . ' INNER JOIN products.category category'
            . ' WHERE category.name = :category_name'
        );
    }

    public function testNestedAssociationInWhere()
    {
        $this->criteria->andWhere(
            $this->criteria::expr()->eq('products.category', 'test_category')
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.products products'
            . ' INNER JOIN products.category category'
            . ' WHERE products.category = :products_category'
        );
    }

    public function testNestedFieldInOrderByAndJoinsAlreadyExist()
    {
        $this->criteria->addLeftJoin('products', '{root}.products')->setAlias('products');
        $this->criteria->addLeftJoin('products.category', '{products}.category')->setAlias('category');
        $this->criteria->orderBy(['products.category.name' => Criteria::ASC]);

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.products products'
            . ' LEFT JOIN products.category category'
            . ' ORDER BY category.name ASC'
        );
    }

    public function testNestedFieldInWhereAndJoinsAlreadyExist()
    {
        $this->criteria->addLeftJoin('products', '{root}.products')->setAlias('products');
        $this->criteria->addLeftJoin('products.category', '{products}.category')->setAlias('category');
        $this->criteria->andWhere(
            $this->criteria::expr()->eq('products.category.name', 'test_category')
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.products products'
            . ' INNER JOIN products.category category'
            . ' WHERE category.name = :category_name'
        );
    }

    public function testNestedFieldWithPlaceholderInWhereAndJoinsAlreadyExist()
    {
        $this->criteria->addLeftJoin('products', '{root}.products')->setAlias('products');
        $this->criteria->addLeftJoin('products.category', '{products}.category')->setAlias('category');
        $this->criteria->andWhere(
            $this->criteria::expr()->eq('{products.category}.name', 'test_category')
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.products products'
            . ' LEFT JOIN products.category category'
            . ' WHERE category.name = :category_name'
        );
    }

    public function testPlaceholdersInOrderBy()
    {
        $this->criteria->addLeftJoin('category', '{root}.category');
        $this->criteria->addLeftJoin('products', '{root}.products');
        $this->criteria->addLeftJoin('products.category', '{products}.category');
        $this->criteria->orderBy(
            [
                '{root}.name'              => Criteria::ASC,
                '{category}.name'          => Criteria::ASC,
                '{products.category}.name' => Criteria::ASC
            ]
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category alias1'
            . ' LEFT JOIN e.products alias2'
            . ' LEFT JOIN alias2.category alias3'
            . ' ORDER BY e.name ASC, alias1.name ASC, alias3.name ASC'
        );
    }

    public function testPlaceholdersInWhere()
    {
        $this->criteria->addLeftJoin('category', '{root}.category');
        $this->criteria->addLeftJoin('products', '{root}.products');
        $this->criteria->addLeftJoin('products.category', '{products}.category');
        $this->criteria->andWhere(
            $this->criteria::expr()->orX(
                $this->criteria::expr()->eq('{root}.name', 'test_user'),
                $this->criteria::expr()->eq('{category}.name', 'test_category'),
                $this->criteria::expr()->eq('{products.category}.name', 'test_category')
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category alias1'
            . ' LEFT JOIN e.products alias2'
            . ' LEFT JOIN alias2.category alias3'
            . ' WHERE e.name = :e_name OR alias1.name = :alias1_name OR alias3.name = :alias3_name'
        );
    }

    public function testAssociationsWithSameName()
    {
        $this->criteria->orderBy(['owner.owner.owner.name' => Criteria::ASC]);

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.owner owner'
            . ' LEFT JOIN owner.owner owner1'
            . ' LEFT JOIN owner1.owner owner2'
            . ' ORDER BY owner2.name ASC'
        );
    }

    public function testCriteriaWhenFirstResultIsNotSet()
    {
        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $this->criteriaConnector->applyCriteria($qb, $this->criteria);

        self::assertNull($qb->getFirstResult());
    }

    public function testCriteriaWithFirstResult()
    {
        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $this->criteria->setFirstResult(12);

        $this->criteriaConnector->applyCriteria($qb, $this->criteria);

        self::assertSame(12, $qb->getFirstResult());
    }

    public function testCriteriaWhenMaxResultsIsNotSet()
    {
        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $this->criteriaConnector->applyCriteria($qb, $this->criteria);

        self::assertNull($qb->getMaxResults());
    }

    public function testCriteriaWithMaxResults()
    {
        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');
        $this->criteria->setMaxResults(3);

        $this->criteriaConnector->applyCriteria($qb, $this->criteria);

        self::assertSame(3, $qb->getMaxResults());
    }
}
