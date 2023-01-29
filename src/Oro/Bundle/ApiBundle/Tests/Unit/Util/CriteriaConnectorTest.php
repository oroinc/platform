<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\Common\Collections\Criteria as CommonCriteria;
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

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CriteriaConnectorTest extends OrmRelatedTestCase
{
    private const ENTITY_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';

    /** @var EntityClassResolver */
    private $entityClassResolver;

    /** @var CriteriaConnector */
    private $criteriaConnector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityClassResolver = new EntityClassResolver($this->doctrine);

        $expressionVisitorFactory = new QueryExpressionVisitorFactory(
            [
                'NOT' => new Expression\NotCompositeExpression(),
                'AND' => new Expression\AndCompositeExpression(),
                'OR'  => new Expression\OrCompositeExpression()
            ],
            [
                '='                 => new Expression\EqComparisonExpression(),
                '<>'                => new Expression\NeqComparisonExpression(),
                'IN'                => new Expression\InComparisonExpression(),
                'NEQ_OR_NULL'       => new Expression\NeqOrNullComparisonExpression(),
                'NEQ_OR_EMPTY'      => new Expression\NeqOrEmptyComparisonExpression(),
                'EXISTS'            => new Expression\ExistsComparisonExpression(),
                'EMPTY'             => new Expression\EmptyComparisonExpression(),
                'ALL_MEMBER_OF'     => new Expression\AllMemberOfComparisonExpression(),
                'ALL_NOT_MEMBER_OF' => new Expression\AllMemberOfComparisonExpression(true)
            ],
            $this->entityClassResolver
        );

        $this->criteriaConnector = new CriteriaConnector(
            new CriteriaNormalizer(
                $this->doctrineHelper,
                new RequireJoinsFieldVisitorFactory(new RequireJoinsDecisionMaker()),
                new OptimizeJoinsFieldVisitorFactory(new OptimizeJoinsDecisionMaker())
            ),
            new CriteriaPlaceholdersResolver(),
            $expressionVisitorFactory,
            $this->entityClassResolver
        );
    }

    private function assertQuery(CommonCriteria $criteria, string $expectedDql): void
    {
        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\User::class, 'e');

        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertEquals(
            $expectedDql,
            str_replace(self::ENTITY_NAMESPACE, 'Test:', $qb->getDQL())
        );
    }

    private static function comparison(string $field, string $operator, mixed $value): Comparison
    {
        return new Comparison($field, $operator, $value);
    }

    private function createCriteria(bool $isCommonCriteria = false): CommonCriteria|Criteria
    {
        if ($isCommonCriteria) {
            return new CommonCriteria();
        }

        return new Criteria($this->entityClassResolver);
    }

    public function criteriaDataProvider(): array
    {
        return [
            [false],
            [true]
        ];
    }

    public function testOrderBy()
    {
        $criteria = $this->createCriteria();
        $criteria->orderBy(['id' => Criteria::ASC, 'category.name' => Criteria::ASC]);
        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' ORDER BY e.id ASC, category.name ASC'
        );
    }

    public function testOrderByAssociation()
    {
        $criteria = $this->createCriteria();
        $criteria->orderBy(['id' => Criteria::ASC, 'category' => Criteria::ASC]);
        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' ORDER BY e.id ASC, e.category ASC'
        );
    }

    public function testWhere()
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category.name', 'test_category'),
                $criteria::expr()->eq('groups.name', 'test_group')
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE category.name = :category_name AND groups.name = :groups_name'
        );
    }

    public function testWhereByAssociation()
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                $criteria::expr()->eq('groups', 'test_group')
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE e.category = :category AND e.groups = :groups'
        );
    }

    public function testShouldOptimizeJoinForExists()
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                self::comparison('category.name', 'EXISTS', true),
                $criteria::expr()->eq('groups.name', 'test_group')
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE category.name IS NOT NULL AND groups.name = :groups_name'
        );
    }

    public function testShouldNotOptimizeJoinForNotExists()
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                self::comparison('category.name', 'EXISTS', false),
                $criteria::expr()->eq('groups.name', 'test_group')
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE category.name IS NULL AND groups.name = :groups_name'
        );
    }

    public function testShouldNotOptimizeJoinForNeqOrNull()
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                self::comparison('category.name', 'NEQ_OR_NULL', new Value('test_category')),
                $criteria::expr()->eq('groups.name', 'test_group')
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE (category.name NOT IN(:category_name) OR category.name IS NULL) AND groups.name = :groups_name'
        );
    }

    public function testShouldNotOptimizeJoinForNeqOrEmpty()
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                $criteria::expr()->in('groups', [123]),
                self::comparison('groups', 'NEQ_OR_EMPTY', 234)
            )
        );

        $this->assertQuery(
            $criteria,
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
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                $criteria::expr()->in('groups', [123]),
                self::comparison('groups', 'EMPTY', true)
            )
        );

        $this->assertQuery(
            $criteria,
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
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                $criteria::expr()->in('groups', [123]),
                self::comparison('groups', 'EMPTY', false)
            )
        );

        $this->assertQuery(
            $criteria,
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
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                $criteria::expr()->in('groups', [123]),
                self::comparison('groups', 'ALL_MEMBER_OF', 234)
            )
        );

        $this->assertQuery(
            $criteria,
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

    public function testShouldNotOptimizeJoinForAllNotMemberOf()
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                $criteria::expr()->in('groups', [123]),
                self::comparison('groups', 'ALL_NOT_MEMBER_OF', 234)
            )
        );

        $this->assertQuery(
            $criteria,
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
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                self::comparison('groups', 'EMPTY', true)
            )
        );

        $this->assertQuery(
            $criteria,
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
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                self::comparison('groups', 'EMPTY', false)
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' WHERE e.category = :category'
            . ' AND EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups)'
        );
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testShouldNotRequireAnyJoinWhenOnlyEmpty(bool $isCommonCriteria)
    {
        $criteria = $this->createCriteria($isCommonCriteria);
        $criteria->andWhere(
            self::comparison('groups', 'EMPTY', true)
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' WHERE NOT(EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups))'
        );
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testShouldNotRequireAnyJoinsWhenOnlyNotEmpty(bool $isCommonCriteria)
    {
        $criteria = $this->createCriteria($isCommonCriteria);
        $criteria->andWhere(
            self::comparison('groups', 'EMPTY', false)
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' WHERE EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups)'
        );
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testShouldNotRequireAnyJoinsWhenOnlyAllMemberOf(bool $isCommonCriteria)
    {
        $criteria = $this->createCriteria($isCommonCriteria);
        $criteria->andWhere(
            self::comparison('groups', 'ALL_MEMBER_OF', 234)
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' WHERE :groups_expected = ('
            . 'SELECT COUNT(groups_subquery1)'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups AND groups_subquery1 IN(:groups))'
        );
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testShouldNotRequireAnyJoinsWhenOnlyAllNotMemberOf(bool $isCommonCriteria)
    {
        $criteria = $this->createCriteria($isCommonCriteria);
        $criteria->andWhere(
            self::comparison('groups', 'ALL_NOT_MEMBER_OF', 234)
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' WHERE :groups_expected = ('
            . 'SELECT COUNT(groups_subquery1)'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups AND groups_subquery1 IN(:groups))'
        );
    }

    public function testNestedFieldInOrderBy()
    {
        $criteria = $this->createCriteria();
        $criteria->orderBy(['id' => Criteria::ASC, 'products.category.name' => Criteria::ASC]);

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.products products'
            . ' LEFT JOIN products.category category'
            . ' ORDER BY e.id ASC, category.name ASC'
        );
    }

    public function testNestedAssociationInOrderBy()
    {
        $criteria = $this->createCriteria();
        $criteria->orderBy(['id' => Criteria::ASC, 'products.category' => Criteria::ASC]);

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.products products'
            . ' LEFT JOIN products.category category'
            . ' ORDER BY e.id ASC, products.category ASC'
        );
    }

    public function testNestedFieldInWhere()
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->eq('products.category.name', 'test_category')
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.products products'
            . ' INNER JOIN products.category category'
            . ' WHERE category.name = :category_name'
        );
    }

    public function testNestedAssociationInWhere()
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->eq('products.category', 'test_category')
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.products products'
            . ' INNER JOIN products.category category'
            . ' WHERE products.category = :products_category'
        );
    }

    public function testNestedFieldInOrderByAndJoinsAlreadyExist()
    {
        $criteria = $this->createCriteria();
        $criteria->addLeftJoin('products', '{root}.products')->setAlias('products');
        $criteria->addLeftJoin('products.category', '{products}.category')->setAlias('category');
        $criteria->orderBy(['products.category.name' => Criteria::ASC]);

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.products products'
            . ' LEFT JOIN products.category category'
            . ' ORDER BY category.name ASC'
        );
    }

    public function testNestedFieldInWhereAndJoinsAlreadyExist()
    {
        $criteria = $this->createCriteria();
        $criteria->addLeftJoin('products', '{root}.products')->setAlias('products');
        $criteria->addLeftJoin('products.category', '{products}.category')->setAlias('category');
        $criteria->andWhere(
            $criteria::expr()->eq('products.category.name', 'test_category')
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.products products'
            . ' INNER JOIN products.category category'
            . ' WHERE category.name = :category_name'
        );
    }

    public function testNestedFieldWithPlaceholderInWhereAndJoinsAlreadyExist()
    {
        $criteria = $this->createCriteria();
        $criteria->addLeftJoin('products', '{root}.products')->setAlias('products');
        $criteria->addLeftJoin('products.category', '{products}.category')->setAlias('category');
        $criteria->andWhere(
            $criteria::expr()->eq('{products.category}.name', 'test_category')
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.products products'
            . ' LEFT JOIN products.category category'
            . ' WHERE category.name = :category_name'
        );
    }

    public function testPlaceholdersInOrderBy()
    {
        $criteria = $this->createCriteria();
        $criteria->addLeftJoin('category', '{root}.category');
        $criteria->addLeftJoin('products', '{root}.products');
        $criteria->addLeftJoin('products.category', '{products}.category');
        $criteria->orderBy(
            [
                '{root}.name'              => Criteria::ASC,
                '{category}.name'          => Criteria::ASC,
                '{products.category}.name' => Criteria::ASC
            ]
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category alias1'
            . ' LEFT JOIN e.products alias2'
            . ' LEFT JOIN alias2.category alias3'
            . ' ORDER BY e.name ASC, alias1.name ASC, alias3.name ASC'
        );
    }

    public function testPlaceholdersInWhere()
    {
        $criteria = $this->createCriteria();
        $criteria->addLeftJoin('category', '{root}.category');
        $criteria->addLeftJoin('products', '{root}.products');
        $criteria->addLeftJoin('products.category', '{products}.category');
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('{root}.name', 'test_user'),
                $criteria::expr()->eq('{category}.name', 'test_category'),
                $criteria::expr()->eq('{products.category}.name', 'test_category')
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category alias1'
            . ' LEFT JOIN e.products alias2'
            . ' LEFT JOIN alias2.category alias3'
            . ' WHERE e.name = :e_name AND alias1.name = :alias1_name AND alias3.name = :alias3_name'
        );
    }

    public function testAssociationsWithSameName()
    {
        $criteria = $this->createCriteria();
        $criteria->orderBy(['owner.owner.owner.name' => Criteria::ASC]);

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.owner owner'
            . ' LEFT JOIN owner.owner owner1'
            . ' LEFT JOIN owner1.owner owner2'
            . ' ORDER BY owner2.name ASC'
        );
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testCriteriaWhenFirstResultIsNotSet(bool $isCommonCriteria)
    {
        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $criteria = $this->createCriteria($isCommonCriteria);
        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertEquals(0, $qb->getFirstResult());
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testCriteriaWithFirstResult(bool $isCommonCriteria)
    {
        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $criteria = $this->createCriteria($isCommonCriteria);
        $criteria->setFirstResult(12);

        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertSame(12, $qb->getFirstResult());
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testCriteriaWhenMaxResultsIsNotSet(bool $isCommonCriteria)
    {
        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $criteria = $this->createCriteria($isCommonCriteria);
        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertNull($qb->getMaxResults());
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testCriteriaWithMaxResults(bool $isCommonCriteria)
    {
        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $criteria = $this->createCriteria($isCommonCriteria);
        $criteria->setMaxResults(3);

        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertSame(3, $qb->getMaxResults());
    }
}
