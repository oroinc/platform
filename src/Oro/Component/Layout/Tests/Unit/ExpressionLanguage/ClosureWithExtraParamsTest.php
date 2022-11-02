<?php

namespace Oro\Component\Layout\Tests\Unit\ExpressionLanguage;

use Oro\Component\Layout\ExpressionLanguage\ClosureWithExtraParams;

class ClosureWithExtraParamsTest extends \PHPUnit\Framework\TestCase
{
    public function testClosureWithExtraParams(): void
    {
        $closure = function () {
            return true;
        };
        $extraParamNames = ['param1'];
        $expression = 'expr';

        $closureWithExtraParams = new ClosureWithExtraParams($closure, $extraParamNames, $expression);
        self::assertSame($closure, $closureWithExtraParams->getClosure());
        self::assertSame($extraParamNames, $closureWithExtraParams->getExtraParamNames());
        self::assertSame($expression, $closureWithExtraParams->getExpression());
    }
}
