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

        $this->assertSame([], $expressionVisitor->getParameters());
    }

    public function testGetParameters()
    {
        $expressionVisitor = new QueryExpressionVisitor();

        $parameter = new Parameter('prm1', 'val1');
        $expressionVisitor->addParameter($parameter);

        $this->assertEquals([$parameter], $expressionVisitor->getParameters());
    }

    public function testAddParameterObject()
    {
        $expressionVisitor = new QueryExpressionVisitor();

        $parameter = new Parameter('prm1', 'val1');
        $expressionVisitor->addParameter($parameter);

        $this->assertEquals([$parameter], $expressionVisitor->getParameters());
    }

    public function testAddParameterWithoutType()
    {
        $expressionVisitor = new QueryExpressionVisitor();

        $expressionVisitor->addParameter('prm1', 'val1');

        $this->assertEquals([new Parameter('prm1', 'val1')], $expressionVisitor->getParameters());
    }

    public function testAddParameterWithType()
    {
        $expressionVisitor = new QueryExpressionVisitor();

        $expressionVisitor->addParameter('prm1', 'val1', 'string');

        $this->assertEquals([new Parameter('prm1', 'val1', 'string')], $expressionVisitor->getParameters());
    }

    public function testCreateParameter()
    {
        $expressionVisitor = new QueryExpressionVisitor();

        $this->assertEquals(
            new Parameter('prm1', 'val1', 'string'),
            $expressionVisitor->createParameter('prm1', 'val1', 'string')
        );
    }

    public function testBuildPlaceholder()
    {
        $expressionVisitor = new QueryExpressionVisitor();
        $this->assertEquals(':test', $expressionVisitor->buildPlaceholder('test'));
    }

    public function testGetExpressionBuilder()
    {
        $expressionVisitor = new QueryExpressionVisitor();

        $this->assertInstanceOf(
            ExpressionBuilder::class,
            $expressionVisitor->getExpressionBuilder()
        );
    }

    public function testWalkValue()
    {
        $expressionVisitor = new QueryExpressionVisitor();
        $value = 'test';
        $this->assertSame($value, $expressionVisitor->walkValue(new Value($value)));
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

        $this->assertInstanceOf('Doctrine\ORM\Query\Expr\Andx', $result);
        $this->assertEquals(
            [
                new OrmComparison('e.test', '=', ':e_test'),
                new OrmComparison('e.id', '=', ':e_id'),
                new OrmComparison('e.id', '=', ':e_id_2'),
            ],
            $result->getParts()
        );
        $this->assertEquals(
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

        $this->assertEquals(
            new Func('e.test IN', [':e_test']),
            $result
        );
        $this->assertEquals(
            [
                new Parameter('e_test', [1, 2, 3], Connection::PARAM_INT_ARRAY)
            ],
            $expressionVisitor->getParameters()
        );
    }

    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage Unknown comparison operator: NOT SUPPORTED
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
}
