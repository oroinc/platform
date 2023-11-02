<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Api\Repository;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationOptionFilterVisitor;

class ConfigurationOptionFilterVisitorTest extends \PHPUnit\Framework\TestCase
{
    private ConfigurationOptionFilterVisitor $visitor;

    protected function setUp(): void
    {
        $this->visitor = new ConfigurationOptionFilterVisitor();
    }

    public function testWalkComparisonForEqExpr(): void
    {
        self::assertSame([], $this->visitor->getFilters());

        $this->visitor->walkComparison(new Comparison('field', Comparison::EQ, 'value'));

        self::assertSame(['field' => 'value'], $this->visitor->getFilters());
    }

    public function testWalkComparisonForInExpr(): void
    {
        self::assertSame([], $this->visitor->getFilters());

        $this->visitor->walkComparison(new Comparison('field', Comparison::IN, ['value1', 'value2']));

        self::assertSame(['field' => ['value1', 'value2']], $this->visitor->getFilters());
    }

    public function testWalkComparisonForNotSupportedExpr(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only "=" and "IN" operators are supported. Field: field.');

        $this->visitor->walkComparison(new Comparison('field', Comparison::NEQ, 'value'));
        $this->visitor->getFilters();
    }

    public function testWalkCompositeExpressionForAndExpr(): void
    {
        self::assertSame([], $this->visitor->getFilters());

        $this->visitor->walkCompositeExpression(
            new CompositeExpression(
                CompositeExpression::TYPE_AND,
                [
                    new Comparison('field1', Comparison::EQ, 'value1'),
                    new Comparison('field2', Comparison::IN, ['value21', 'value22'])
                ]
            )
        );

        self::assertSame(
            ['field1' => 'value1', 'field2' => ['value21', 'value22']],
            $this->visitor->getFilters()
        );
    }

    public function testWalkCompositeExpressionForOrExpr(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only "AND" expression is supported.');

        $this->visitor->walkCompositeExpression(
            new CompositeExpression(
                CompositeExpression::TYPE_OR,
                [
                    new Comparison('field1', Comparison::EQ, 'value1'),
                    new Comparison('field2', Comparison::IN, ['value21', 'value22'])
                ]
            )
        );
        $this->visitor->getFilters();
    }
}
