<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Orx;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\OrCompositeExpression;
use PHPUnit\Framework\TestCase;

class OrCompositeExpressionTest extends TestCase
{
    public function testWalkCompositeExpression(): void
    {
        $expression = new OrCompositeExpression();
        $expressionList = [
            new Comparison('e.test', '=', ':e_test'),
            new Comparison('e.id', '=', ':e_id')
        ];

        $result = $expression->walkCompositeExpression($expressionList);

        self::assertInstanceOf(Orx::class, $result);
        self::assertEquals($expressionList, $result->getParts());
    }
}
