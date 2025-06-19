<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\NotCompositeExpression;
use PHPUnit\Framework\TestCase;

class NotCompositeExpressionTest extends TestCase
{
    public function testWalkCompositeExpression(): void
    {
        $expression = new NotCompositeExpression();
        $expressionList = [
            new Comparison('e.test', '=', ':e_test'),
            new Comparison('e.id', '=', ':e_id')
        ];

        $result = $expression->walkCompositeExpression($expressionList);

        self::assertInstanceOf(Func::class, $result);
        self::assertEquals('NOT', $result->getName());
        self::assertEquals($expressionList, $result->getArguments());
    }
}
