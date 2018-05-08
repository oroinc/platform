<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\NeqOrNullComparisonExpression;

class NeqOrNullComparisonExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testWalkComparisonExpression()
    {
        $expression = new NeqOrNullComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor();
        $fieldName = 'e.test';
        $parameterName = 'test_1';
        $value = 'text';

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $fieldName,
            $parameterName,
            $value
        );

        self::assertInstanceOf(Orx::class, $result);
        self::assertEquals(
            [
                new Comparison($fieldName, '<>', ':' . $parameterName),
                $fieldName . ' IS NULL'

            ],
            $result->getParts()
        );
        self::assertEquals(
            [new Parameter($parameterName, $value, \PDO::PARAM_STR)],
            $expressionVisitor->getParameters()
        );
    }
}
