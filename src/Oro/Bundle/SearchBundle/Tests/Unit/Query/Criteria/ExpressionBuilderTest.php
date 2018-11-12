<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query\Criteria;

use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;

class ExpressionBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionBuilder */
    private $builder;

    public function setUp()
    {
        $this->builder = new ExpressionBuilder();
    }

    public function testNotContains()
    {
        $comparison = $this->builder->notContains('test_field', 'test_value');
        $this->assertEquals(
            new Comparison('test_field', Comparison::NOT_CONTAINS, new Value('test_value')),
            $comparison
        );
    }

    public function testStartsWith()
    {
        $comparison = $this->builder->startsWith('test_field', 'test_value');
        $this->assertEquals(
            new Comparison('test_field', Comparison::STARTS_WITH, new Value('test_value')),
            $comparison
        );
    }

    public function testExists()
    {
        $comparison = $this->builder->exists('test_field');
        $this->assertEquals(
            new Comparison('test_field', Comparison::EXISTS, new Value(null)),
            $comparison
        );
    }

    public function testNotExists()
    {
        $comparison = $this->builder->notExists('test_field');
        $this->assertEquals(
            new Comparison('test_field', Comparison::NOT_EXISTS, new Value(null)),
            $comparison
        );
    }

    public function testLike()
    {
        $comparison = $this->builder->like('test_field', 'test_value');
        $this->assertEquals(
            new Comparison('test_field', Comparison::LIKE, new Value('test_value')),
            $comparison
        );
    }

    public function testNotLike()
    {
        $comparison = $this->builder->notLike('test_field', 'test_value');
        $this->assertEquals(
            new Comparison('test_field', Comparison::NOT_LIKE, new Value('test_value')),
            $comparison
        );
    }
}
