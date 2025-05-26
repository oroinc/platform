<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\AllMemberOfComparisonExpression;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Stub\FieldDqlExpressionProviderStub;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class AllMemberOfComparisonExpressionTest extends OrmRelatedTestCase
{
    public function testWalkComparisonExpressionWhenAssociationIsNotJoined(): void
    {
        $expression = new AllMemberOfComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = $field;
        $parameterName = 'groups_1';
        $value = [1, 2, 3];
        $expectedNumberOfRecordsParameterName = $parameterName . '_expected';

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
            $value
        );

        $expectedSubquery = 'SELECT COUNT(groups_subquery1)'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups AND groups_subquery1 IN(:groups_1)';

        self::assertEquals(
            new Comparison(
                ':' . $expectedNumberOfRecordsParameterName,
                Comparison::EQ,
                '(' . $expectedSubquery . ')'
            ),
            $result
        );
        self::assertEquals(
            [
                new Parameter($parameterName, $value),
                new Parameter($expectedNumberOfRecordsParameterName, count($value))
            ],
            $expressionVisitor->getParameters()
        );
    }

    public function testWalkComparisonExpressionForArrayValue(): void
    {
        $expression = new AllMemberOfComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = $field;
        $parameterName = 'groups_1';
        $value = [1, 2, 3];
        $expectedNumberOfRecordsParameterName = $parameterName . '_expected';

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

        $expectedSubquery = 'SELECT COUNT(groups_subquery1)'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups AND groups_subquery1 IN(:groups_1)';

        self::assertEquals(
            new Comparison(
                ':' . $expectedNumberOfRecordsParameterName,
                Comparison::EQ,
                '(' . $expectedSubquery . ')'
            ),
            $result
        );
        self::assertEquals(
            [
                new Parameter($parameterName, $value),
                new Parameter($expectedNumberOfRecordsParameterName, count($value))
            ],
            $expressionVisitor->getParameters()
        );
    }

    public function testWalkComparisonExpressionForScalarValue(): void
    {
        $expression = new AllMemberOfComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = $field;
        $parameterName = 'groups_1';
        $value = 123;
        $expectedNumberOfRecordsParameterName = $parameterName . '_expected';

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

        $expectedSubquery = 'SELECT COUNT(groups_subquery1)'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups AND groups_subquery1 IN(:groups_1)';

        self::assertEquals(
            new Comparison(
                ':' . $expectedNumberOfRecordsParameterName,
                Comparison::EQ,
                '(' . $expectedSubquery . ')'
            ),
            $result
        );
        self::assertEquals(
            [
                new Parameter($parameterName, $value),
                new Parameter($expectedNumberOfRecordsParameterName, 1)
            ],
            $expressionVisitor->getParameters()
        );
    }

    public function testWalkComparisonExpressionWhenLastElementInPathIsField(): void
    {
        $expression = new AllMemberOfComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups.name';
        $expr = $field;
        $parameterName = 'groups_1';
        $value = 123;
        $expectedNumberOfRecordsParameterName = $parameterName . '_expected';

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

        $expectedSubquery = 'SELECT COUNT(groups_subquery1)'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups AND groups_subquery1.name IN(:groups_1)';

        self::assertEquals(
            new Comparison(
                ':' . $expectedNumberOfRecordsParameterName,
                Comparison::EQ,
                '(' . $expectedSubquery . ')'
            ),
            $result
        );
        self::assertEquals(
            [
                new Parameter($parameterName, $value),
                new Parameter($expectedNumberOfRecordsParameterName, 1)
            ],
            $expressionVisitor->getParameters()
        );
    }

    public function testWalkComparisonExpressionForCustomExpression(): void
    {
        $expression = new AllMemberOfComparisonExpression();
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new FieldDqlExpressionProviderStub(),
            new EntityClassResolver($this->doctrine)
        );
        $field = 'e.groups';
        $expr = sprintf('e.id = {entity:%s}.id', Entity\Group::class);
        $parameterName = 'groups_1';
        $value = [1, 2, 3];
        $expectedNumberOfRecordsParameterName = $parameterName . '_expected';

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
            $value
        );

        $expectedSubquery = 'SELECT COUNT(groups_subquery1)'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE e.id = groups_subquery1.id AND groups_subquery1 IN(:groups_1)';

        self::assertEquals(
            new Comparison(
                ':' . $expectedNumberOfRecordsParameterName,
                Comparison::EQ,
                '(' . $expectedSubquery . ')'
            ),
            $result
        );
        self::assertEquals(
            [
                new Parameter($parameterName, $value),
                new Parameter($expectedNumberOfRecordsParameterName, count($value))
            ],
            $expressionVisitor->getParameters()
        );
    }
}
