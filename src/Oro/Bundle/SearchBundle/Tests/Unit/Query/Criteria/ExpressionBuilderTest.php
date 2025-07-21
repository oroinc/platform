<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query\Criteria;

use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use PHPUnit\Framework\TestCase;

class ExpressionBuilderTest extends TestCase
{
    private ExpressionBuilder $builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->builder = new ExpressionBuilder();
    }

    public function testNotContains(): void
    {
        $comparison = $this->builder->notContains('test_field', 'test_value');
        $this->assertEquals(
            new Comparison('test_field', Comparison::NOT_CONTAINS, new Value('test_value')),
            $comparison
        );
    }

    public function testStartsWith(): void
    {
        $comparison = $this->builder->startsWith('test_field', 'test_value');
        $this->assertEquals(
            new Comparison('test_field', Comparison::STARTS_WITH, new Value('test_value')),
            $comparison
        );
    }

    public function testExists(): void
    {
        $comparison = $this->builder->exists('test_field');
        $this->assertEquals(
            new Comparison('test_field', Comparison::EXISTS, new Value(null)),
            $comparison
        );
    }

    public function testNotExists(): void
    {
        $comparison = $this->builder->notExists('test_field');
        $this->assertEquals(
            new Comparison('test_field', Comparison::NOT_EXISTS, new Value(null)),
            $comparison
        );
    }

    public function testLike(): void
    {
        $comparison = $this->builder->like('test_field', 'test_value');
        $this->assertEquals(
            new Comparison('test_field', Comparison::LIKE, new Value('test_value')),
            $comparison
        );
    }

    public function testNotLike(): void
    {
        $comparison = $this->builder->notLike('test_field', 'test_value');
        $this->assertEquals(
            new Comparison('test_field', Comparison::NOT_LIKE, new Value('test_value')),
            $comparison
        );
    }
}
