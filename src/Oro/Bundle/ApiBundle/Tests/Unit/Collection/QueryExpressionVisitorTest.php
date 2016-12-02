<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Expr\Comparison as OrmComparison;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\AndCompositeExpression;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\EqComparisonExpression;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\InComparisonExpression;

class QueryExpressionVisitorTest extends \PHPUnit_Framework_TestCase
{
    public function testParameters()
    {
        $expressionVisitor = new QueryExpressionVisitor();
        $expressionVisitor->addParameter('first');
        $expressionVisitor->addParameter(2);
        $expressionVisitor->addParameter([3]);

        $this->assertEquals(
            new ArrayCollection(['first', 2, [3]]),
            $expressionVisitor->getParameters()
        );
    }

    public function testBuildPlaceholder()
    {
        $expressionVisitor = new QueryExpressionVisitor();
        $this->assertEquals(':test', $expressionVisitor->buildPlaceholder('test'));
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
            $expressionVisitor->getParameters()->toArray()
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
            $expressionVisitor->getParameters()->toArray()
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
