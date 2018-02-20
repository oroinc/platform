<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Comparison as OrmComparison;
use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\MemberOfComparisonExpression;

class MemberOfComparisonExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testWalkComparisonExpression()
    {
        $expression = new MemberOfComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor();
        $comparison = new Comparison('test', 'MEMBER OF', 54);
        $fieldName = 'a.test';
        $parameterName = 'test_2';

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $comparison,
            $fieldName,
            $parameterName
        );

        $this->assertEquals(
            new OrmComparison(':test_2', 'MEMBER OF', 'a.test'),
            $result
        );

        $this->assertEquals(
            [new Parameter('test_2', 54, 'integer')],
            $expressionVisitor->getParameters()
        );
    }
}
