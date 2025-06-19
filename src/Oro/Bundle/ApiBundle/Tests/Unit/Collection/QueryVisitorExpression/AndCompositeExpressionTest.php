<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\AndCompositeExpression;
use PHPUnit\Framework\TestCase;

class AndCompositeExpressionTest extends TestCase
{
    public function testWalkCompositeExpression(): void
    {
        $expression = new AndCompositeExpression();
        $expressionList = [
            new Comparison('e.test', '=', ':e_test'),
            new Comparison('e.id', '=', ':e_id')
        ];

        $result = $expression->walkCompositeExpression($expressionList);

        self::assertInstanceOf(Andx::class, $result);
        self::assertEquals($expressionList, $result->getParts());
    }
}
