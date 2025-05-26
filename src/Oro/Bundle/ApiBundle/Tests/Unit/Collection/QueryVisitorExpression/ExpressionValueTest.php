<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\ExpressionValue;
use PHPUnit\Framework\TestCase;

class ExpressionValueTest extends TestCase
{
    public function testExpressionValue(): void
    {
        $expressionValue = new ExpressionValue('Value', 'EXPR(%s)');
        self::assertEquals('Value', $expressionValue->getValue());
        self::assertEquals('EXPR(:parameter)', $expressionValue->buildExpression(':parameter'));
    }
}
