<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\EmptyValueComparisonExpression;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class EmptyValueComparisonExpressionTest extends \PHPUnit\Framework\TestCase
{
    private function getExpressionVisitor(): QueryExpressionVisitor
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            ['empty' => new EmptyValueComparisonExpression()],
            $this->createMock(EntityClassResolver::class)
        );
        $expressionVisitor->setQueryAliases(['e']);

        return $expressionVisitor;
    }

    /**
     * @dataProvider stringFieldDataProvider
     */
    public function testWalkComparisonExpressionForStringFieldAndValueIsTrue(string $dataType): void
    {
        $expressionVisitor = $this->getExpressionVisitor();

        $result = $expressionVisitor->walkComparison(
            new Comparison('e.test', 'empty/:' . $dataType, true)
        );

        self::assertEquals(
            new Expr\Orx([
                'e.test IS NULL',
                new Expr\Comparison('e.test', Expr\Comparison::EQ, ':e_test')
            ]),
            $result
        );
        self::assertEquals(
            [new Parameter('e_test', '')],
            $expressionVisitor->getParameters()
        );
    }

    /**
     * @dataProvider stringFieldDataProvider
     */
    public function testWalkComparisonExpressionForStringFieldAndValueIsFalse(string $dataType): void
    {
        $expressionVisitor = $this->getExpressionVisitor();

        $result = $expressionVisitor->walkComparison(
            new Comparison('e.test', 'empty/:' . $dataType, false)
        );

        self::assertEquals(
            new Expr\Andx([
                'e.test IS NOT NULL',
                new Expr\Comparison('e.test', Expr\Comparison::NEQ, ':e_test')
            ]),
            $result
        );
        self::assertEquals(
            [new Parameter('e_test', '')],
            $expressionVisitor->getParameters()
        );
    }

    public function stringFieldDataProvider(): array
    {
        return [
            ['string'],
            ['text'],
            ['simple_array']
        ];
    }

    /**
     * @dataProvider jsonFieldDataProvider
     */
    public function testWalkComparisonExpressionForJsonFieldAndValueIsTrue(string $dataType): void
    {
        $expressionVisitor = $this->getExpressionVisitor();

        $result = $expressionVisitor->walkComparison(
            new Comparison('e.test', 'empty/:' . $dataType, true)
        );

        self::assertEquals(
            new Expr\Orx([
                'e.test IS NULL',
                new Expr\Comparison('LENGTH(CAST(e.test AS text))', Expr\Comparison::EQ, ':e_test')
            ]),
            $result
        );
        self::assertEquals(
            [new Parameter('e_test', 2)],
            $expressionVisitor->getParameters()
        );
    }

    /**
     * @dataProvider jsonFieldDataProvider
     */
    public function testWalkComparisonExpressionForJsonFieldAndValueIsFalse(string $dataType): void
    {
        $expressionVisitor = $this->getExpressionVisitor();

        $result = $expressionVisitor->walkComparison(
            new Comparison('e.test', 'empty/:' . $dataType, false)
        );

        self::assertEquals(
            new Expr\Andx([
                'e.test IS NOT NULL',
                new Expr\Comparison('LENGTH(CAST(e.test AS text))', Expr\Comparison::GT, ':e_test')
            ]),
            $result
        );
        self::assertEquals(
            [new Parameter('e_test', 2)],
            $expressionVisitor->getParameters()
        );
    }

    public function jsonFieldDataProvider(): array
    {
        return [
            ['json'],
            ['json_array']
        ];
    }

    public function testWalkComparisonExpressionForArrayFieldAndValueIsTrue(): void
    {
        $expressionVisitor = $this->getExpressionVisitor();

        $result = $expressionVisitor->walkComparison(
            new Comparison('e.test', 'empty/:array', true)
        );

        self::assertEquals(
            new Expr\Orx([
                new Expr\Comparison('CAST(e.test AS text)', Expr\Comparison::EQ, ':e_test_null'),
                new Expr\Comparison('CAST(e.test AS text)', Expr\Comparison::EQ, ':e_test')
            ]),
            $result
        );
        self::assertEquals(
            [
                new Parameter('e_test_null', base64_encode(serialize(null))),
                new Parameter('e_test', base64_encode(serialize([])))
            ],
            $expressionVisitor->getParameters()
        );
    }

    public function testWalkComparisonExpressionForArrayFieldAndValueIsFalse(): void
    {
        $expressionVisitor = $this->getExpressionVisitor();

        $result = $expressionVisitor->walkComparison(
            new Comparison('e.test', 'empty/:array', false)
        );

        self::assertEquals(
            new Expr\Andx([
                new Expr\Comparison('CAST(e.test AS text)', Expr\Comparison::NEQ, ':e_test_null'),
                new Expr\Comparison('CAST(e.test AS text)', Expr\Comparison::NEQ, ':e_test')
            ]),
            $result
        );
        self::assertEquals(
            [
                new Parameter('e_test_null', base64_encode(serialize(null))),
                new Parameter('e_test', base64_encode(serialize([])))
            ],
            $expressionVisitor->getParameters()
        );
    }
}
