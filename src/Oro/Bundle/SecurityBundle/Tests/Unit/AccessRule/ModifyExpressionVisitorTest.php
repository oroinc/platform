<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\ExpressionInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Value;

class ModifyExpressionVisitorTest extends \PHPUnit\Framework\TestCase
{
    private ModifyExpressionVisitorStub $visitor;

    protected function setUp(): void
    {
        $this->visitor = new ModifyExpressionVisitorStub();
    }

    private function createExpression(): ExpressionInterface
    {
        return new CompositeExpression(
            CompositeExpression::TYPE_AND,
            [
                new Comparison(new Path('f1'), Comparison::EQ, 'v1'),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison(new Path('f2'), Comparison::EQ, 'v2'),
                        new Comparison(new Path('f3'), Comparison::EQ, 'v3')
                    ]
                )
            ]
        );
    }

    public function testExpressionNotChanged(): void
    {
        $expr = $this->createExpression();

        $resultExpr = $expr->visit($this->visitor);

        self::assertEquals($expr, $resultExpr);
        self::assertNotSame($expr, $resultExpr);
    }

    public function testExpressionChanged(): void
    {
        $expr = $this->createExpression();
        $expectedExpr = new CompositeExpression(
            CompositeExpression::TYPE_AND,
            [
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison(new Path('f1'), Comparison::EQ, 'v1'),
                        new Comparison(new Path('f4'), Comparison::EQ, 'v4')
                    ]
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison(new Path('f2'), Comparison::EQ, 'v2'),
                        new Comparison(new Path('f3'), Comparison::EQ, 'v3')
                    ]
                )
            ]
        );

        $this->visitor->setWalkComparisonCallback(function (Comparison $comparison) {
            $leftOperand = $comparison->getLeftOperand();
            if ($leftOperand instanceof Path && $leftOperand->getField() === 'f1') {
                return new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        $comparison,
                        new Comparison(new Path('f4'), Comparison::EQ, 'v4')
                    ]
                );
            }

            return $comparison;
        });

        $resultExpr = $expr->visit($this->visitor);

        self::assertEquals($expectedExpr, $resultExpr);
        self::assertNotSame($expectedExpr, $resultExpr);
    }

    public function testExpressionValueChanged(): void
    {
        $expr = $this->createExpression();
        $expectedExpr = new CompositeExpression(
            CompositeExpression::TYPE_AND,
            [
                new Comparison(new Path('f1'), Comparison::EQ, 'new v1'),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison(new Path('f2'), Comparison::EQ, 'v2'),
                        new Comparison(new Path('f3'), Comparison::EQ, 'v3')
                    ]
                )
            ]
        );

        $this->visitor->setWalkValueCallback(function (Value $value) {
            if ($value->getValue() === 'v1') {
                return new Value('new v1');
            }

            return $value;
        });

        $resultExpr = $expr->visit($this->visitor);

        self::assertEquals($expectedExpr, $resultExpr);
        self::assertNotSame($expectedExpr, $resultExpr);
    }

    public function testRemoveComparisonInCompositeExpression(): void
    {
        $expr = $this->createExpression();
        $expectedExpr = new CompositeExpression(
            CompositeExpression::TYPE_OR,
            [
                new Comparison(new Path('f2'), Comparison::EQ, 'v2'),
                new Comparison(new Path('f3'), Comparison::EQ, 'v3')
            ]
        );

        $this->visitor->setWalkComparisonCallback(function (Comparison $comparison) {
            $leftOperand = $comparison->getLeftOperand();
            if ($leftOperand instanceof Path && $leftOperand->getField() === 'f1') {
                return null;
            }

            return $comparison;
        });

        $resultExpr = $expr->visit($this->visitor);

        self::assertEquals($expectedExpr, $resultExpr);
        self::assertNotSame($expectedExpr, $resultExpr);
    }

    public function testRemoveAllComparisonsInCompositeExpression(): void
    {
        $expr = $this->createExpression();
        $expectedExpr = new Comparison(new Path('f1'), Comparison::EQ, 'v1');

        $this->visitor->setWalkComparisonCallback(function (Comparison $comparison) {
            $leftOperand = $comparison->getLeftOperand();
            if ($leftOperand instanceof Path && in_array($leftOperand->getField(), ['f2', 'f3'], true)) {
                return null;
            }

            return $comparison;
        });

        $resultExpr = $expr->visit($this->visitor);

        self::assertEquals($expectedExpr, $resultExpr);
        self::assertNotSame($expectedExpr, $resultExpr);
    }
}
