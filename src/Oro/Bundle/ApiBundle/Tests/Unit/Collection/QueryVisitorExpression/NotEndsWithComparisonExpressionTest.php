<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\ExpressionValue;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\NotEndsWithComparisonExpression;
use Oro\Bundle\ApiBundle\Tests\Unit\Stub\FieldDqlExpressionProviderStub;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use PHPUnit\Framework\TestCase;

class NotEndsWithComparisonExpressionTest extends TestCase
{
    public function testWalkComparisonExpression(): void
    {
        $expression = new NotEndsWithComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
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
            [new Parameter($parameterName, '%' . $value)],
            $expressionVisitor->getParameters()
        );
    }

    public function testWalkComparisonExpressionWithExpressionValue(): void
    {
        $expression = new NotEndsWithComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            $this->createMock(EntityClassResolver::class)
        );
        $field = 'e.test';
        $expr = 'LOWER(e.test)';
        $parameterName = 'test_1';
        $value = new ExpressionValue('text', 'LOWER(%s)');

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );

        self::assertEquals(
            new Comparison($expr, 'NOT LIKE', 'LOWER(:' . $parameterName . ')'),
            $result
        );
        self::assertEquals(
            [new Parameter($parameterName, '%' . $value->getValue())],
            $expressionVisitor->getParameters()
        );
    }
}
