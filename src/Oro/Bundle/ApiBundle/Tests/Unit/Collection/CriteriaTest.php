<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Collection\Join;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CriteriaTest extends OrmRelatedTestCase
{
    const ENTITY_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';

    /** @var Criteria */
    protected $criteria;

    protected function setUp()
    {
        parent::setUp();

        $this->criteria = new Criteria(new EntityClassResolver($this->doctrine), 'a%s');
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

    /**
     * @param $expectedDql
     */
    protected function assertQuery($expectedDql)
    {
        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from($this->getEntityClass('User'), 'e');

        $this->criteria->completeJoins();
        $this->doctrineHelper->applyCriteria($qb, $this->criteria);

        $this->assertEquals(
            $expectedDql,
            str_replace(self::ENTITY_NAMESPACE, 'Test:', $qb->getDQL())
        );
    }

    public function joinTypeDataProvider()
    {
        return [
            [Join::INNER_JOIN, 'addInnerJoin'],
            [Join::LEFT_JOIN, 'addLeftJoin'],
        ];
    }

    public function testEmptyJoins()
    {
        $this->assertCount(0, $this->criteria->getJoins());
    }

    /**
     * @dataProvider joinTypeDataProvider
     */
    public function testAddJoin($joinType, $addMethodName)
    {
        $this->criteria->{$addMethodName}('products', '{root}.products');

        $expectedJoin = new Join($joinType, '{root}.products');
        $this->assertTrue($this->criteria->hasJoin('products'));
        $this->assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        $this->assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
        $this->assertQuery(
            'SELECT e FROM Test:User e ' . $joinType . ' JOIN e.products a1'
        );
    }

    /**
     * @dataProvider joinTypeDataProvider
     */
    public function testAddJoinWithCondition($joinType, $addMethodName)
    {
        $this->criteria->{$addMethodName}(
            'products',
            $this->getEntityClass('Product'),
            Join::WITH,
            '{entity}.name IS NOT NULL',
            'idx_name'
        );

        $expectedJoin = new Join(
            $joinType,
            $this->getEntityClass('Product'),
            Join::WITH,
            '{entity}.name IS NOT NULL',
            'idx_name'
        );
        $this->assertTrue($this->criteria->hasJoin('products'));
        $this->assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        $this->assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
        $this->assertQuery(
            'SELECT e FROM Test:User e '
            . $joinType . ' JOIN Test:Product a1 INDEX BY idx_name WITH a1.name IS NOT NULL'
        );
    }

    /**
     * @dataProvider joinTypeDataProvider
     */
    public function testAddJoinWithConditionAndEntityName($joinType, $addMethodName)
    {
        $this->criteria->{$addMethodName}('products', 'Test:Product', Join::WITH, '{entity}.name IS NOT NULL');

        $expectedJoin = new Join(
            $joinType,
            $this->getEntityClass('Product'),
            Join::WITH,
            '{entity}.name IS NOT NULL'
        );
        $this->assertTrue($this->criteria->hasJoin('products'));
        $this->assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        $this->assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
        $this->assertQuery(
            'SELECT e FROM Test:User e '
            . $joinType . ' JOIN Test:Product a1 WITH a1.name IS NOT NULL'
        );
    }

    public function testAddSeveralJoins()
    {
        $this->criteria->addLeftJoin(
            'roles',
            'Test:Role',
            Join::WITH,
            '{root}.id MEMBER OF {entity}.users'
        );
        $this->criteria->addLeftJoin(
            'roles.users',
            '{roles}.users'
        );
        $this->criteria->addLeftJoin(
            'products',
            'Test:Product',
            Join::WITH,
            '{entity}.owner = {root}'
        );
        $this->criteria->addLeftJoin(
            'products.owner',
            '{products}.owner',
            Join::WITH,
            '{entity}.id = {roles.users}.id'
        );

        $expectedJoins = [
            'roles'          => new Join(
                Join::LEFT_JOIN,
                $this->getEntityClass('Role'),
                Join::WITH,
                '{root}.id MEMBER OF {entity}.users'
            ),
            'roles.users'    => new Join(
                Join::LEFT_JOIN,
                '{roles}.users'
            ),
            'products'       => new Join(
                Join::LEFT_JOIN,
                $this->getEntityClass('Product'),
                Join::WITH,
                '{entity}.owner = {root}'
            ),
            'products.owner' => new Join(
                Join::LEFT_JOIN,
                '{products}.owner',
                Join::WITH,
                '{entity}.id = {roles.users}.id'
            ),
        ];
        $this->assertEquals($expectedJoins, $this->criteria->getJoins());
        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN Test:Role a1 WITH e.id MEMBER OF a1.users'
            . ' LEFT JOIN a1.users a2'
            . ' LEFT JOIN Test:Product a3 WITH a3.owner = e'
            . ' LEFT JOIN a3.owner a4 WITH a4.id = a2.id'
        );
    }

    /**
     * @dataProvider joinTypeDataProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $propertyPath must be specified.
     */
    public function testAddJoinWithEmptyPropertyPath($joinType, $addMethodName)
    {
        $this->criteria->{$addMethodName}('', 'Test:Product');
    }

    /**
     * @dataProvider joinTypeDataProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $join must be specified. Join path: "products".
     */
    public function testAddJoinWithEmptyJoinExpr($joinType, $addMethodName)
    {
        $this->criteria->{$addMethodName}('products', '');
    }

    /**
     * @dataProvider joinTypeDataProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage "Test1:Product" is not valid entity name. Join path: "products".
     */
    public function testAddJoinWithInvalidEntity($joinType, $addMethodName)
    {
        $this->criteria->{$addMethodName}('products', 'Test1:Product');
    }

    /**
     * @dataProvider joinTypeDataProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $conditionType must be specified if $condition exists. Join path: "products".
     */
    public function testAddJoinWithConditionButWithoutConditionType($joinType, $addMethodName)
    {
        $this->criteria->{$addMethodName}('products', 'Test:Product', '', '{entity}.name IS NOT NULL');
    }

    /**
     * @dataProvider joinTypeDataProvider
     */
    public function testAddJoinConflictsWithExistingJoin($joinType, $addMethodName)
    {
        $this->setExpectedException(
            '\LogicException',
            'The join definition for "products" conflicts with already added join.'
            . ' Existing join: "LEFT JOIN ' . $this->getEntityClass('Product') . '".'
            . ' New join: "' . $joinType . ' JOIN ' . $this->getEntityClass('Category') . '".'
        );
        $this->criteria->addLeftJoin('products', 'Test:Product');
        $this->criteria->{$addMethodName}('products', 'Test:Category');
    }

    /**
     * @dataProvider joinTypeDataProvider
     */
    public function testAddSameJoinTwice($joinType, $addMethodName)
    {
        $this->criteria->{$addMethodName}('products', '{root}.products');
        $this->criteria->{$addMethodName}('products', '{root}.products');

        $expectedJoin = new Join($joinType, '{root}.products');
        $this->assertTrue($this->criteria->hasJoin('products'));
        $this->assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        $this->assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
        $this->assertQuery(
            'SELECT e FROM Test:User e ' . $joinType . ' JOIN e.products a1'
        );
    }

    public function testAddInnerJoinAndThenLeftJoinForSameJoinStatement()
    {
        $this->criteria->addInnerJoin('products', '{root}.products');
        $this->criteria->addLeftJoin('products', '{root}.products');

        $expectedJoin = new Join(Join::INNER_JOIN, '{root}.products');
        $this->assertTrue($this->criteria->hasJoin('products'));
        $this->assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        $this->assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
        $this->assertQuery(
            'SELECT e FROM Test:User e INNER JOIN e.products a1'
        );
    }

    public function testAddLeftJoinAndThenInnerJoinForSameJoinStatement()
    {
        $this->criteria->addLeftJoin('products', '{root}.products');
        $this->criteria->addInnerJoin('products', '{root}.products');

        $expectedJoin = new Join(Join::INNER_JOIN, '{root}.products');
        $this->assertTrue($this->criteria->hasJoin('products'));
        $this->assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        $this->assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
        $this->assertQuery(
            'SELECT e FROM Test:User e INNER JOIN e.products a1'
        );
    }

    public function testCompleteJoinsForOrderBy()
    {
        $this->criteria->orderBy(['id' => Criteria::ASC, 'category.name' => Criteria::ASC]);
        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' ORDER BY e.id ASC, category.name ASC'
        );
    }

    public function testCompleteJoinsForWhere()
    {
        $this->criteria->andWhere(
            $this->criteria->expr()->orX(
                $this->criteria->expr()->eq('category.name', 'test_category'),
                $this->criteria->expr()->eq('groups.name', 'test_group')
            )
        );

        /**
         * Changed expected result according to changes
         * in user defined sorting algorithm in php7
         * https://bugs.php.net/bug.php?id=69158
         */
        if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
            $this->assertQuery(
                'SELECT e FROM Test:User e'
                . ' LEFT JOIN e.category category'
                . ' LEFT JOIN e.groups groups'
                . ' WHERE category.name = :category_name OR groups.name = :groups_name'
            );
        } else {
            $this->assertQuery(
                'SELECT e FROM Test:User e'
                . ' LEFT JOIN e.groups groups'
                . ' LEFT JOIN e.category category'
                . ' WHERE category.name = :category_name OR groups.name = :groups_name'
            );
        }
    }
}
