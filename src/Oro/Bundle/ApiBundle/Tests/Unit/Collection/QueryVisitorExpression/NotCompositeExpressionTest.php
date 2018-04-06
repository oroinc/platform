<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr\Comparison;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\NotCompositeExpression;

class NotCompositeExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testWalkCompositeExpression()
    {
        $expression = new NotCompositeExpression();
        $expressionList = [
            new Comparison('e.test', '=', ':e_test'),
            new Comparison('e.id', '=', ':e_id')
        ];

        $result = $expression->walkCompositeExpression($expressionList);

        $this->assertInstanceOf('Doctrine\ORM\Query\Expr\Func', $result);
        $this->assertEquals('NOT', $result->getName());
        $this->assertEquals(
            $expressionList,
            $result->getArguments()
        );
    }
}
