<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\NotStartsWithComparisonExpression;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class NotStartsWithComparisonExpressionTest extends \PHPUnit\Framework\TestCase
{
    public function testWalkComparisonExpression()
    {
        $expression = new NotStartsWithComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            $this->createMock(EntityClassResolver::class)
        );
        $field = 'e.test';
        $expr = 'LOWER(e.test)';
        $parameterName = 'test_1';
        $value = 'text';

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );

        self::assertEquals(
            new Comparison($expr, 'NOT LIKE', ':' . $parameterName),
            $result
        );
        self::assertEquals(
            [new Parameter($parameterName, $value . '%', \PDO::PARAM_STR)],
            $expressionVisitor->getParameters()
        );
    }
}
