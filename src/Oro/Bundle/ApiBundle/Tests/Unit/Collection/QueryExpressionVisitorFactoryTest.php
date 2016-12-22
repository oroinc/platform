<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitorFactory;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\ComparisonExpressionInterface;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\CompositeExpressionInterface;

class QueryExpressionVisitorFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateExpressionVisitor()
    {
        $compositeExpressions = ['AND' => $this->createMock(CompositeExpressionInterface::class)];
        $comparisonExpressions = ['=' => $this->createMock(ComparisonExpressionInterface::class)];

        $factory = new QueryExpressionVisitorFactory(
            $compositeExpressions,
            $comparisonExpressions
        );

        $expressionVisitor = $factory->createExpressionVisitor();

        $this->assertEquals(
            new QueryExpressionVisitor($compositeExpressions, $comparisonExpressions),
            $expressionVisitor
        );
    }
}
