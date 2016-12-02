<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Parameter;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\InComparisonExpression;

class InComparisonExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testWalkComparisonExpression()
    {
        $expression = new InComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor();
        $comparison = new Comparison('test', 'IN', [1, 2, 3]);
        $parameterName = 'test_2';
        $field = 'a.test';

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $comparison,
            $parameterName,
            $field
        );

        $this->assertEquals(
            new Func('a.test IN', [':test_2']),
            $result
        );

        $this->assertEquals(
            [new Parameter('test_2', [1, 2, 3], Connection::PARAM_INT_ARRAY)],
            $expressionVisitor->getParameters()->toArray()
        );
    }
}
