<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitorFactory;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression as Expression;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\ApiBundle\Util\CriteriaNormalizer;
use Oro\Bundle\ApiBundle\Util\CriteriaPlaceholdersResolver;
use Oro\Bundle\ApiBundle\Util\OptimizeJoinsDecisionMaker;
use Oro\Bundle\ApiBundle\Util\OptimizeJoinsFieldVisitorFactory;
use Oro\Bundle\ApiBundle\Util\RequireJoinsDecisionMaker;
use Oro\Bundle\ApiBundle\Util\RequireJoinsFieldVisitorFactory;
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
            [
                'NOT' => new Expression\NotCompositeExpression(),
                'AND' => new Expression\AndCompositeExpression(),
                'OR'  => new Expression\OrCompositeExpression()
            ],
            [
                '='             => new Expression\EqComparisonExpression(),
                '<>'            => new Expression\NeqComparisonExpression(),
                'IN'            => new Expression\InComparisonExpression(),
                'NEQ_OR_NULL'   => new Expression\NeqOrNullComparisonExpression(),
                'NEQ_OR_EMPTY'  => new Expression\NeqOrEmptyComparisonExpression(),
                'EXISTS'        => new Expression\ExistsComparisonExpression(),
                'EMPTY'         => new Expression\EmptyComparisonExpression(),
                'ALL_MEMBER_OF' => new Expression\AllMemberOfComparisonExpression()
            ],
            $entityClassResolver
        );
        $this->criteriaConnector = new CriteriaConnector(
            new CriteriaNormalizer(
                $this->doctrineHelper,
                new RequireJoinsFieldVisitorFactory(new RequireJoinsDecisionMaker()),
                new OptimizeJoinsFieldVisitorFactory(new OptimizeJoinsDecisionMaker())
            ),
            new CriteriaPlaceholdersResolver(),
            $this->expressionVisitorFactory,
            $entityClassResolver
        );
    }

    /**
     * @param string $expectedDql
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

    /**
     * @param string $field
     * @param string $operator
     * @param mixed  $value
     *
     * @return Comparison
     */
    private static function comparison($field, $operator, $value)
    {
        return new Comparison($field, $operator, $value);
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
            $this->criteria::expr()->andX(
                $this->criteria::expr()->eq('category.name', 'test_category'),
                $this->criteria::expr()->eq('groups.name', 'test_group')
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE category.name = :category_name AND groups.name = :groups_name'
        );
    }

    public function testWhereByAssociation()
    {
        $this->criteria->andWhere(
            $this->criteria::expr()->andX(
                $this->criteria::expr()->eq('category', 'test_category'),
                $this->criteria::expr()->eq('groups', 'test_group')
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE e.category = :category AND e.groups = :groups'
        );
    }

    public function testShouldOptimizeJoinForExists()
    {
        $this->criteria->andWhere(
            $this->criteria::expr()->andX(
                self::comparison('category.name', 'EXISTS', true),
                $this->criteria::expr()->eq('groups.name', 'test_group')
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE category.name IS NOT NULL AND groups.name = :groups_name'
        );
    }

    public function testShouldNotOptimizeJoinForNotExists()
    {
        $this->criteria->andWhere(
            $this->criteria::expr()->andX(
                self::comparison('category.name', 'EXISTS', false),
                $this->criteria::expr()->eq('groups.name', 'test_group')
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE category.name IS NULL AND groups.name = :groups_name'
        );
    }

    public function testShouldNotOptimizeJoinForNeqOrNull()
    {
        $this->criteria->andWhere(
            $this->criteria::expr()->andX(
                self::comparison('category.name', 'NEQ_OR_NULL', new Value('test_category')),
                $this->criteria::expr()->eq('groups.name', 'test_group')
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE (category.name NOT IN(:category_name) OR category.name IS NULL) AND groups.name = :groups_name'
        );
    }

    public function testShouldNotOptimizeJoinForNeqOrEmpty()
    {
        $this->criteria->andWhere(
            $this->criteria::expr()->andX(
                $this->criteria::expr()->eq('category', 'test_category'),
                $this->criteria::expr()->in('groups', [123]),
                self::comparison('groups', 'NEQ_OR_EMPTY', 234)
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' LEFT JOIN e.groups groups'
            . ' WHERE e.category = :category'
            . ' AND e.groups IN(:groups)'
            . ' AND (NOT(EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups AND groups_subquery1 IN(:groups_2))))'
        );
    }

    public function testShouldNotOptimizeJoinForEmpty()
    {
        $this->criteria->andWhere(
            $this->criteria::expr()->andX(
                $this->criteria::expr()->eq('category', 'test_category'),
                $this->criteria::expr()->in('groups', [123]),
                self::comparison('groups', 'EMPTY', true)
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' LEFT JOIN e.groups groups'
            . ' WHERE e.category = :category'
            . ' AND e.groups IN(:groups)'
            . ' AND NOT(EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups))'
        );
    }

    public function testShouldOptimizeJoinForNotEmpty()
    {
        $this->criteria->andWhere(
            $this->criteria::expr()->andX(
                $this->criteria::expr()->eq('category', 'test_category'),
                $this->criteria::expr()->in('groups', [123]),
                self::comparison('groups', 'EMPTY', false)
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE e.category = :category'
            . ' AND e.groups IN(:groups)'
            . ' AND EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups)'
        );
    }

    public function testShouldNotOptimizeJoinForAllMemberOf()
    {
        $this->criteria->andWhere(
            $this->criteria::expr()->andX(
                $this->criteria::expr()->eq('category', 'test_category'),
                $this->criteria::expr()->in('groups', [123]),
                self::comparison('groups', 'ALL_MEMBER_OF', 234)
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' LEFT JOIN e.groups groups'
            . ' WHERE e.category = :category'
            . ' AND e.groups IN(:groups)'
            . ' AND (:groups_2_expected = ('
            . 'SELECT COUNT(groups_subquery1)'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups AND groups_subquery1 IN(:groups_2)))'
        );
    }

    public function testShouldNotRequireJoinForEmpty()
    {
        $this->criteria->andWhere(
            $this->criteria::expr()->andX(
                $this->criteria::expr()->eq('category', 'test_category'),
                self::comparison('groups', 'EMPTY', true)
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' WHERE e.category = :category'
            . ' AND NOT(EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups))'
        );
    }

    public function testShouldRequireJoinForNotEmpty()
    {
        $this->criteria->andWhere(
            $this->criteria::expr()->andX(
                $this->criteria::expr()->eq('category', 'test_category'),
                self::comparison('groups', 'EMPTY', false)
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' WHERE e.category = :category'
            . ' AND EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups)'
        );
    }

    public function testShouldNotRequireAnyJoinWhenOnlyEmpty()
    {
        $this->criteria->andWhere(
            self::comparison('groups', 'EMPTY', true)
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' WHERE NOT(EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups))'
        );
    }

    public function testShouldRequireAnyJoinsWhenOnlyNotEmpty()
    {
        $this->criteria->andWhere(
            self::comparison('groups', 'EMPTY', false)
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' WHERE EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups)'
        );
    }

    public function testShouldRequireAnyJoinsWhenOnlyAllMemberOf()
    {
        $this->criteria->andWhere(
            self::comparison('groups', 'ALL_MEMBER_OF', 234)
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' WHERE :groups_expected = ('
            . 'SELECT COUNT(groups_subquery1)'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups AND groups_subquery1 IN(:groups))'
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
            $this->criteria::expr()->andX(
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
            . ' WHERE e.name = :e_name AND alias1.name = :alias1_name AND alias3.name = :alias3_name'
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
