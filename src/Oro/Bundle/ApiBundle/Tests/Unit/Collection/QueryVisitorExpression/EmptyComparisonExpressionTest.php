<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\EmptyComparisonExpression;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Stub\FieldDqlExpressionProviderStub;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class EmptyComparisonExpressionTest extends OrmRelatedTestCase
{
    public function testWalkComparisonExpressionForTrueValue()
    {
        $expression = new EmptyComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = $field;
        $parameterName = 'groups_1';
        $value = true;

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
            . ' WHERE groups_subquery1 = groups';

        self::assertEquals(
            new Expr\Func('NOT', [new Expr\Func('EXISTS', [$expectedSubquery])]),
            $result
        );
        self::assertEmpty($expressionVisitor->getParameters());
    }

    public function testWalkComparisonExpressionForFalseValue()
    {
        $expression = new EmptyComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = $field;
        $parameterName = 'groups_1';
        $value = false;

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
            . ' WHERE groups_subquery1 = groups';

        self::assertEquals(
            new Expr\Func('EXISTS', [$expectedSubquery]),
            $result
        );
        self::assertEmpty($expressionVisitor->getParameters());
    }

    public function testWalkComparisonExpressionWhenLastElementInPathIsField()
    {
        $expression = new EmptyComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups.name';
        $expr = $field;
        $parameterName = 'groups_1';
        $value = true;

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
            . ' WHERE groups_subquery1 = groups';

        self::assertEquals(
            new Expr\Func('NOT', [new Expr\Func('EXISTS', [$expectedSubquery])]),
            $result
        );
        self::assertEmpty($expressionVisitor->getParameters());
    }

    public function testWalkComparisonExpressionForCustomExpression()
    {
        $expression = new EmptyComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = sprintf('e.id = {entity:%s}.id', Entity\Group::class);
        $parameterName = 'groups_1';

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
            true
        );

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups';

        self::assertEquals(
            new Expr\Func('NOT', [new Expr\Func('EXISTS', [$expectedSubquery])]),
            $result
        );
        self::assertEmpty($expressionVisitor->getParameters());
    }

    public function testWalkComparisonExpressionForCustomExpressionWhenAssociationAlreadyJoined()
    {
        $expression = new EmptyComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = sprintf('e.id = {entity:%s}.id', Entity\Group::class);
        $parameterName = 'groups_1';

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\User::class, 'e');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryAliases(['e']);
        $expressionVisitor->setQueryJoinMap([]);

        $result = $expression->walkComparisonExpression(
            $expressionVisitor,
            $field,
            $expr,
            $parameterName,
            true
        );

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE e.id = groups_subquery1.id';

        self::assertEquals(
            new Expr\Func('NOT', [new Expr\Func('EXISTS', [$expectedSubquery])]),
            $result
        );
        self::assertEmpty($expressionVisitor->getParameters());
    }
}
