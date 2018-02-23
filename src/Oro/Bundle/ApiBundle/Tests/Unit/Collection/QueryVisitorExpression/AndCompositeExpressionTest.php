<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr\Comparison;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\AndCompositeExpression;

class AndCompositeExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testWalkCompositeExpression()
    {
        $expression = new AndCompositeExpression();
        $expressionList = [
            new Comparison('e.test', '=', ':e_test'),
            new Comparison('e.id', '=', ':e_id')
        ];

        $result = $expression->walkCompositeExpression($expressionList);

        $this->assertInstanceOf('Doctrine\ORM\Query\Expr\Andx', $result);
        $this->assertEquals(
            $expressionList,
            $result->getParts()
        );
    }
}
