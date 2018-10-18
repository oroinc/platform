<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\MemberOfComparisonExpression;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class MemberOfComparisonExpressionTest extends OrmRelatedTestCase
{
    public function testWalkComparisonExpression()
    {
        $expression = new MemberOfComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            $this->createMock(EntityClassResolver::class)
        );
        $field = 'e.test';
        $expr = 'LOWER(e.test)';
        $parameterName = 'test_1';
        $value = 123;

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );

        self::assertEquals(
            new Expr\Comparison(':' . $parameterName, 'MEMBER OF', $field),
            $result
        );
        self::assertEquals(
            [new Parameter($parameterName, $value, 'integer')],
            $expressionVisitor->getParameters()
        );
    }

    public function testWalkComparisonExpressionForRangeValue()
    {
        $expression = new MemberOfComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = 'LOWER(e.groups)';
        $parameterName = 'groups_1';
        $fromValue = 123;
        $toValue = 234;
        $value = new Range($fromValue, $toValue);

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\User::class, 'e')
            ->innerJoin('e.groups', 'groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryAliases(['e', 'groups']);
        $expressionVisitor->setQueryJoinMap(['groups' => 'groups']);

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            $value
        );

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups'
            . ' AND (groups_subquery1 BETWEEN :groups_1_from AND :groups_1_to)';

        self::assertEquals(
            new Expr\Func('EXISTS', [$expectedSubquery]),
            $result
        );
        self::assertEquals(
            [
                new Parameter('groups_1_from', $fromValue, 'integer'),
                new Parameter('groups_1_to', $toValue, 'integer')
            ],
            $expressionVisitor->getParameters()
        );
    }
}
