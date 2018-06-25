<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\ExistsComparisonExpression;

class ExistsComparisonExpressionTest extends \PHPUnit\Framework\TestCase
{
    public function testWalkComparisonExpressionForTrueValue()
    {
        $expression = new ExistsComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor();
        $fieldName = 'e.test';
        $parameterName = 'test_1';
        $value = true;

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $fieldName,
            $parameterName,
            $value
        );

        self::assertEquals($fieldName . ' IS NOT NULL', $result);
        self::assertEmpty($expressionVisitor->getParameters());
    }

    public function testWalkComparisonExpressionForFalseValue()
    {
        $expression = new ExistsComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor();
        $fieldName = 'e.test';
        $parameterName = 'test_1';
        $value = false;

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $fieldName,
            $parameterName,
            $value
        );

        self::assertEquals($fieldName . ' IS NULL', $result);
        self::assertEmpty($expressionVisitor->getParameters());
    }
}
