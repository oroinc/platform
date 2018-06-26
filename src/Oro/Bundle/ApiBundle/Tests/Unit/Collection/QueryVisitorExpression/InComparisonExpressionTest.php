<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\InComparisonExpression;

class InComparisonExpressionTest extends \PHPUnit\Framework\TestCase
{
    public function testWalkComparisonExpression()
    {
        $expression = new InComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor();
        $fieldName = 'e.test';
        $parameterName = 'test_1';
        $value = [1, 2, 3];

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $fieldName,
            $parameterName,
            $value
        );

        self::assertEquals(
            new Func($fieldName . ' IN', [':' . $parameterName]),
            $result
        );
        self::assertEquals(
            [new Parameter($parameterName, $value, Connection::PARAM_INT_ARRAY)],
            $expressionVisitor->getParameters()
        );
    }
}
