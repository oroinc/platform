<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\NinComparisonExpression;

class NinComparisonExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testWalkComparisonExpression()
    {
        $expression = new NinComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor();
        $comparison = new Comparison('test', 'NIN', [1, 2, 3]);
        $fieldName = 'a.test';
        $parameterName = 'test_2';

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $comparison,
            $fieldName,
            $parameterName
        );

        $this->assertEquals(
            new Func('a.test NOT IN', [':test_2']),
            $result
        );

        $this->assertEquals(
            [new Parameter('test_2', [1, 2, 3], Connection::PARAM_INT_ARRAY)],
            $expressionVisitor->getParameters()
        );
    }
}
