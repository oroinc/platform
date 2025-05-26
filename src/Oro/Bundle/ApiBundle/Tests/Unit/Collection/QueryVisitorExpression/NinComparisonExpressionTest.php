<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\ExpressionValue;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\NinComparisonExpression;
use Oro\Bundle\ApiBundle\Tests\Unit\Stub\FieldDqlExpressionProviderStub;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use PHPUnit\Framework\TestCase;

class NinComparisonExpressionTest extends TestCase
{
    public function testWalkComparisonExpression(): void
    {
        $expression = new NinComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            $this->createMock(EntityClassResolver::class)
        );
        $field = 'e.test';
        $expr = 'LOWER(e.test)';
        $parameterName = 'test_1';
        $value = [1, 2, 3];

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );

        self::assertEquals(
            new Func($expr . ' NOT IN', [':' . $parameterName]),
            $result
        );
        self::assertEquals(
            [new Parameter($parameterName, $value)],
            $expressionVisitor->getParameters()
        );
    }

    public function testWalkComparisonExpressionWithExpressionValue(): void
    {
        $expression = new NinComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            $this->createMock(EntityClassResolver::class)
        );
        $field = 'e.test';
        $expr = 'LOWER(e.test)';
        $parameterName = 'test_1';
        $values = [1, 2, 3];
        $value = new ExpressionValue($values, 'LOWER(%s)');

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );

        self::assertEquals(
            new Func($expr . ' NOT IN', sprintf('LOWER(:%1$s__1), LOWER(:%1$s__2), LOWER(:%1$s__3)', $parameterName)),
            $result
        );
        self::assertEquals(
            [
                new Parameter($parameterName . '__1', $values[0]),
                new Parameter($parameterName . '__2', $values[1]),
                new Parameter($parameterName . '__3', $values[2])
            ],
            $expressionVisitor->getParameters()
        );
    }
}
