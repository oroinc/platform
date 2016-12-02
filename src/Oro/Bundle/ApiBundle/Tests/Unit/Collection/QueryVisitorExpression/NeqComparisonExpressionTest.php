<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Comparison as OrmComparison;
use Doctrine\ORM\Query\Parameter;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\NeqComparisonExpression;

class NeqComparisonExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testWalkComparisonExpression()
    {
        $expression = new NeqComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor();
        $comparison = new Comparison('test', 'NEQ', 'text');
        $parameterName = 'test_2';
        $field = 'a.test';

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $comparison,
            $parameterName,
            $field
        );

        $this->assertEquals(
            new OrmComparison('a.test', '<>', ':test_2'),
            $result
        );

        $this->assertEquals(
            [new Parameter('test_2', 'text', \PDO::PARAM_STR)],
            $expressionVisitor->getParameters()->toArray()
        );
    }

    public function testWalkComparisonExpressionOnNullValue()
    {
        $expression = new NeqComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor();
        $comparison = new Comparison('test', 'NEQ', null);
        $parameterName = 'test_2';
        $field = 'a.test';

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $comparison,
            $parameterName,
            $field
        );

        $this->assertEquals(
            'a.test IS NOT NULL',
            $result
        );

        $this->assertEmpty(
            $expressionVisitor->getParameters()->toArray()
        );
    }
}
