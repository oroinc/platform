<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr as ExpressionBuilder;
use Doctrine\ORM\Query\Expr\Comparison as OrmComparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\AndCompositeExpression;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\EqComparisonExpression;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\InComparisonExpression;

class QueryExpressionVisitorTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEmptyParameters()
    {
        $expressionVisitor = new QueryExpressionVisitor();

        self::assertSame([], $expressionVisitor->getParameters());
    }

    public function testGetParameters()
    {
        $expressionVisitor = new QueryExpressionVisitor();

        $parameter = new Parameter('prm1', 'val1');
        $expressionVisitor->addParameter($parameter);

        self::assertEquals([$parameter], $expressionVisitor->getParameters());
    }

    public function testAddParameterObject()
    {
        $expressionVisitor = new QueryExpressionVisitor();

        $parameter = new Parameter('prm1', 'val1');
        $expressionVisitor->addParameter($parameter);

        self::assertEquals([$parameter], $expressionVisitor->getParameters());
    }

    public function testAddParameterWithoutType()
    {
        $expressionVisitor = new QueryExpressionVisitor();

        $expressionVisitor->addParameter('prm1', 'val1');

        self::assertEquals([new Parameter('prm1', 'val1')], $expressionVisitor->getParameters());
    }

    public function testAddParameterWithType()
    {
        $expressionVisitor = new QueryExpressionVisitor();

        $expressionVisitor->addParameter('prm1', 'val1', 'string');

        self::assertEquals([new Parameter('prm1', 'val1', 'string')], $expressionVisitor->getParameters());
    }

    public function testCreateParameter()
    {
        $expressionVisitor = new QueryExpressionVisitor();

        self::assertEquals(
            new Parameter('prm1', 'val1', 'string'),
            $expressionVisitor->createParameter('prm1', 'val1', 'string')
        );
    }

    public function testBuildPlaceholder()
    {
        $expressionVisitor = new QueryExpressionVisitor();
        self::assertEquals(':test', $expressionVisitor->buildPlaceholder('test'));
    }

    public function testGetExpressionBuilder()
    {
        $expressionVisitor = new QueryExpressionVisitor();

        self::assertInstanceOf(
            ExpressionBuilder::class,
            $expressionVisitor->getExpressionBuilder()
        );
    }

    public function testWalkValue()
    {
        $expressionVisitor = new QueryExpressionVisitor();
        $value = 'test';
        self::assertSame($value, $expressionVisitor->walkValue(new Value($value)));
    }

    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage Unknown composite NOT SUPPORTED
     */
    public function testWalkCompositeExpressionOnNonSupportedExpressionType()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            ['AND' => new AndCompositeExpression()],
            ['IN' => new InComparisonExpression()]
        );

        $expressionVisitor->setQueryAliases(['e']);
        $expr = new CompositeExpression('NOT SUPPORTED', [new Comparison('e.test', 'IN', [1])]);
        $expressionVisitor->walkCompositeExpression($expr);
    }

    public function testWalkCompositeExpression()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            ['AND' => new AndCompositeExpression()],
            ['=' => new EqComparisonExpression()]
        );

        $expressionVisitor->setQueryAliases(['e']);
        $expr = new CompositeExpression(
            'AND',
            [new Comparison('e.test', '=', 1), new Comparison('e.id', '=', 12), new Comparison('e.id', '=', 25)]
        );
        /** @var Andx $result */
        $result = $expressionVisitor->walkCompositeExpression($expr);

        self::assertInstanceOf(Andx::class, $result);
        self::assertEquals(
            [
                new OrmComparison('e.test', '=', ':e_test'),
                new OrmComparison('e.id', '=', ':e_id'),
                new OrmComparison('e.id', '=', ':e_id_2'),
            ],
            $result->getParts()
        );
        self::assertEquals(
            [
                new Parameter('e_test', 1, 'integer'),
                new Parameter('e_id', 12, 'integer'),
                new Parameter('e_id_2', 25, 'integer'),
            ],
            $expressionVisitor->getParameters()
        );
    }

    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage No aliases are set before invoking walkComparison().
     */
    public function testWalkComparisonWithoutAliases()
    {
        $expressionVisitor = new QueryExpressionVisitor();

        $comparison = new Comparison('e.test', '=', 1);
        $expressionVisitor->walkComparison($comparison);
    }

    public function testWalkComparison()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            ['IN' => new InComparisonExpression()]
        );

        $expressionVisitor->setQueryAliases(['e']);
        $comparison = new Comparison('e.test', 'IN', [1, 2, 3]);
        $result = $expressionVisitor->walkComparison($comparison);

        self::assertEquals(
            new Func('e.test IN', [':e_test']),
            $result
        );
        self::assertEquals(
            [
                new Parameter('e_test', [1, 2, 3], Connection::PARAM_INT_ARRAY)
            ],
            $expressionVisitor->getParameters()
        );
    }

    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage Unknown comparison operator "NOT SUPPORTED".
     */
    public function testWalkComparisonWithUnknownOperator()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            ['AND' => new AndCompositeExpression()],
            ['IN' => new InComparisonExpression(), '=' => new EqComparisonExpression()]
        );

        $expressionVisitor->setQueryAliases(['e']);
        $comparison = new Comparison('e.test', 'NOT SUPPORTED', 21);
        $expressionVisitor->walkComparison($comparison);
    }

    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage Unknown modifier "a" for comparison operator "=".
     */
    public function testWalkComparisonWithUnknownModifierOfOperator()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            ['=' => new EqComparisonExpression()]
        );

        $expressionVisitor->setQueryAliases(['e']);
        $comparison = new Comparison('e.test', '=/a', 21);
        $expressionVisitor->walkComparison($comparison);
    }

    public function testWalkComparisonWithUnknownCaseInsensitiveModifierOfOperator()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            ['=' => new EqComparisonExpression()]
        );

        $expressionVisitor->setQueryAliases(['e']);
        $comparison = new Comparison('e.test', '=/i', 'test value');
        $result = $expressionVisitor->walkComparison($comparison);

        self::assertEquals(
            new OrmComparison('LOWER(e.test)', '=', ':e_test'),
            $result
        );
        self::assertEquals(
            [
                new Parameter('e_test', 'test value', \PDO::PARAM_STR)
            ],
            $expressionVisitor->getParameters()
        );
    }
}
