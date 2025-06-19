<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Collection\Join;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Role;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CriteriaTest extends OrmRelatedTestCase
{
    private Criteria $criteria;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->criteria = new Criteria(new EntityClassResolver($this->doctrine));
    }

    public function joinTypeDataProvider(): array
    {
        return [
            [Join::INNER_JOIN, 'addInnerJoin'],
            [Join::LEFT_JOIN, 'addLeftJoin']
        ];
    }

    public function testEmptyJoins(): void
    {
        self::assertCount(0, $this->criteria->getJoins());
    }

    /**
     * @dataProvider joinTypeDataProvider
     */
    public function testAddJoin(string $joinType, string $addMethodName): void
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
    public function testAddJoinWithCondition(string $joinType, string $addMethodName): void
    {
        $this->criteria->{$addMethodName}(
            'products',
            Product::class,
            Join::WITH,
            '{entity}.name IS NOT NULL',
            'idx_name'
        );

        $expectedJoin = new Join(
            $joinType,
            Product::class,
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
    public function testAddJoinWithConditionAndEntityName(string $joinType, string $addMethodName): void
    {
        $this->criteria->{$addMethodName}('products', Product::class, Join::WITH, '{entity}.name IS NOT NULL');

        $expectedJoin = new Join(
            $joinType,
            Product::class,
            Join::WITH,
            '{entity}.name IS NOT NULL'
        );
        self::assertTrue($this->criteria->hasJoin('products'));
        self::assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        self::assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
    }

    public function testAddSeveralJoins(): void
    {
        $this->criteria->addLeftJoin(
            'roles',
            Role::class,
            Join::WITH,
            '{root}.id MEMBER OF {entity}.users'
        );
        $this->criteria->addLeftJoin(
            'roles.users',
            '{roles}.users'
        );
        $this->criteria->addLeftJoin(
            'products',
            Product::class,
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
                Role::class,
                Join::WITH,
                '{root}.id MEMBER OF {entity}.users'
            ),
            'roles.users'    => new Join(
                Join::LEFT_JOIN,
                '{roles}.users'
            ),
            'products'       => new Join(
                Join::LEFT_JOIN,
                Product::class,
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
     */
    public function testAddJoinWithEmptyPropertyPath(string $joinType, string $addMethodName): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The property path must be not empty.');

        $this->criteria->{$addMethodName}('', Product::class);
    }

    /**
     * @dataProvider joinTypeDataProvider
     */
    public function testAddJoinWithEmptyJoinExpr(string $joinType, string $addMethodName): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The join must be be not empty. Join path: "products".');

        $this->criteria->{$addMethodName}('products', '');
    }

    /**
     * @dataProvider joinTypeDataProvider
     */
    public function testAddJoinWithInvalidEntity(string $joinType, string $addMethodName): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Incorrect entity name: Test1:Product. Expected the full class name.');

        $this->criteria->{$addMethodName}('products', 'Test1:Product');
    }

    /**
     * @dataProvider joinTypeDataProvider
     */
    public function testAddJoinWithConditionButWithoutConditionType(string $joinType, string $addMethodName): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The condition type must be specified if the condition exists. Join path: "products".'
        );

        $this->criteria->{$addMethodName}('products', Product::class, '', '{entity}.name IS NOT NULL');
    }

    /**
     * @dataProvider joinTypeDataProvider
     */
    public function testAddJoinConflictsWithExistingJoin(string $joinType, string $addMethodName): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The join definition for "products" conflicts with already added join.'
            . ' Existing join: "LEFT JOIN ' . Product::class . '".'
            . ' New join: "' . $joinType . ' JOIN ' . Category::class . '".'
        );
        $this->criteria->addLeftJoin('products', Product::class);
        $this->criteria->{$addMethodName}('products', Category::class);
    }

    /**
     * @dataProvider joinTypeDataProvider
     */
    public function testAddSameJoinTwice(string $joinType, string $addMethodName): void
    {
        $this->criteria->{$addMethodName}('products', '{root}.products');
        $this->criteria->{$addMethodName}('products', '{root}.products');

        $expectedJoin = new Join($joinType, '{root}.products');
        self::assertTrue($this->criteria->hasJoin('products'));
        self::assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        self::assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
    }

    public function testAddInnerJoinAndThenLeftJoinForSameJoinStatement(): void
    {
        $this->criteria->addInnerJoin('products', '{root}.products');
        $this->criteria->addLeftJoin('products', '{root}.products');

        $expectedJoin = new Join(Join::INNER_JOIN, '{root}.products');
        self::assertTrue($this->criteria->hasJoin('products'));
        self::assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        self::assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
    }

    public function testAddLeftJoinAndThenInnerJoinForSameJoinStatement(): void
    {
        $this->criteria->addLeftJoin('products', '{root}.products');
        $this->criteria->addInnerJoin('products', '{root}.products');

        $expectedJoin = new Join(Join::INNER_JOIN, '{root}.products');
        self::assertTrue($this->criteria->hasJoin('products'));
        self::assertEquals($expectedJoin, $this->criteria->getJoin('products'));
        self::assertEquals(['products' => $expectedJoin], $this->criteria->getJoins());
    }
}
