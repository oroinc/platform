<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Collection\Join;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CriteriaTest extends OrmRelatedTestCase
{
    /** @var Criteria */
    private $criteria;

    protected function setUp()
    {
        parent::setUp();

        $this->criteria = new Criteria(new EntityClassResolver($this->doctrine));
    }

    public function joinTypeDataProvider()
    {
        return [
            [Join::INNER_JOIN, 'addInnerJoin'],
            [Join::LEFT_JOIN, 'addLeftJoin']
        ];
    }

    public function testEmptyJoins()
    {
        self::assertCount(0, $this->criteria->getJoins());
    }

    /**
     * @dataProvider joinTypeDataProvider
     */
    public function testAddJoin($joinType, $addMethodName)
    {
        $this->criteria->{$addMethodName}('products', '{root}.products');

        $expectedJoin = new Join($joinType, '{root}.products');
        self::assertTrue($this->criteria->hasJoin('products'));
        self::assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        self::assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
    }

    /**
     * @dataProvider joinTypeDataProvider
     */
    public function testAddJoinWithCondition($joinType, $addMethodName)
    {
        $this->criteria->{$addMethodName}(
            'products',
            Entity\Product::class,
            Join::WITH,
            '{entity}.name IS NOT NULL',
            'idx_name'
        );

        $expectedJoin = new Join(
            $joinType,
            Entity\Product::class,
            Join::WITH,
            '{entity}.name IS NOT NULL',
            'idx_name'
        );
        self::assertTrue($this->criteria->hasJoin('products'));
        self::assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        self::assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
    }

    /**
     * @dataProvider joinTypeDataProvider
     */
    public function testAddJoinWithConditionAndEntityName($joinType, $addMethodName)
    {
        $this->criteria->{$addMethodName}('products', 'Test:Product', Join::WITH, '{entity}.name IS NOT NULL');

        $expectedJoin = new Join(
            $joinType,
            Entity\Product::class,
            Join::WITH,
            '{entity}.name IS NOT NULL'
        );
        self::assertTrue($this->criteria->hasJoin('products'));
        self::assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        self::assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
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
                Entity\Role::class,
                Join::WITH,
                '{root}.id MEMBER OF {entity}.users'
            ),
            'roles.users'    => new Join(
                Join::LEFT_JOIN,
                '{roles}.users'
            ),
            'products'       => new Join(
                Join::LEFT_JOIN,
                Entity\Product::class,
                Join::WITH,
                '{entity}.owner = {root}'
            ),
            'products.owner' => new Join(
                Join::LEFT_JOIN,
                '{products}.owner',
                Join::WITH,
                '{entity}.id = {roles.users}.id'
            )
        ];
        self::assertEquals($expectedJoins, $this->criteria->getJoins());
    }

    /**
     * @dataProvider joinTypeDataProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The property path must be not empty.
     */
    public function testAddJoinWithEmptyPropertyPath($joinType, $addMethodName)
    {
        $this->criteria->{$addMethodName}('', 'Test:Product');
    }

    /**
     * @dataProvider joinTypeDataProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The join must be be not empty. Join path: "products".
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
     * @expectedExceptionMessage The condition type must be specified if the condition exists. Join path: "products".
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
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The join definition for "products" conflicts with already added join.'
            . ' Existing join: "LEFT JOIN ' . Entity\Product::class . '".'
            . ' New join: "' . $joinType . ' JOIN ' . Entity\Category::class . '".'
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
        self::assertTrue($this->criteria->hasJoin('products'));
        self::assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        self::assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
    }

    public function testAddInnerJoinAndThenLeftJoinForSameJoinStatement()
    {
        $this->criteria->addInnerJoin('products', '{root}.products');
        $this->criteria->addLeftJoin('products', '{root}.products');

        $expectedJoin = new Join(Join::INNER_JOIN, '{root}.products');
        self::assertTrue($this->criteria->hasJoin('products'));
        self::assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        self::assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
    }

    public function testAddLeftJoinAndThenInnerJoinForSameJoinStatement()
    {
        $this->criteria->addLeftJoin('products', '{root}.products');
        $this->criteria->addInnerJoin('products', '{root}.products');

        $expectedJoin = new Join(Join::INNER_JOIN, '{root}.products');
        self::assertTrue($this->criteria->hasJoin('products'));
        self::assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        self::assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
    }
}
