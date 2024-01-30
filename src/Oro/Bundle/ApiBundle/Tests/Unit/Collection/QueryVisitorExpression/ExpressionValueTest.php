<?php

namespace Unit\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\ExpressionValue;

class ExpressionValueTest extends \PHPUnit\Framework\TestCase
{
    public function testExpressionValue(): void
    {
        $expressionValue = new ExpressionValue('Value', 'EXPR(%s)');
        self::assertEquals('Value', $expressionValue->getValue());
        self::assertEquals('EXPR(:parameter)', $expressionValue->buildExpression(':parameter'));
    }
}
