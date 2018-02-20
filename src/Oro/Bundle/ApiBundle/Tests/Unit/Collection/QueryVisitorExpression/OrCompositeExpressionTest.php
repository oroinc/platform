<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr\Comparison;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\OrCompositeExpression;

class OrCompositeExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testWalkCompositeExpression()
    {
        $expression = new OrCompositeExpression();
        $expressionList = [
            new Comparison('e.test', '=', ':e_test'),
            new Comparison('e.id', '=', ':e_id')
        ];

        $result = $expression->walkCompositeExpression($expressionList);

        $this->assertInstanceOf('Doctrine\ORM\Query\Expr\Orx', $result);
        $this->assertEquals(
            $expressionList,
            $result->getParts()
        );
    }
}
