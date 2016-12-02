<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitorFactory;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\AndCompositeExpression;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\InComparisonExpression;

class QueryExpressionVisitorFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetExpressionVisitor()
    {
        $compositeExpressions = ['AND' => new AndCompositeExpression()];
        $comparisonExpressions = ['IN' => new InComparisonExpression()];

        $factory = new QueryExpressionVisitorFactory(
            $compositeExpressions,
            $comparisonExpressions
        );

        $expressionVisitor = $factory->getExpressionVisitor();

        $this->assertEquals(
            new QueryExpressionVisitor($compositeExpressions, $comparisonExpressions),
            $expressionVisitor
        );
    }
}
