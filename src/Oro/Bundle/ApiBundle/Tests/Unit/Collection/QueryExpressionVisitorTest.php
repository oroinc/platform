<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Expr as QueryExpr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\AndCompositeExpression;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\EqComparisonExpression;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\InComparisonExpression;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class QueryExpressionVisitorTest extends OrmRelatedTestCase
{
    public function testGetEmptyParameters()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            $this->createMock(EntityClassResolver::class)
        );

        self::assertSame([], $expressionVisitor->getParameters());
    }

    public function testGetParameters()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            $this->createMock(EntityClassResolver::class)
        );

        $parameter = new Parameter('prm1', 'val1');
        $expressionVisitor->addParameter($parameter);

        self::assertEquals([$parameter], $expressionVisitor->getParameters());
    }

    public function testAddParameterObject()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            $this->createMock(EntityClassResolver::class)
        );

        $parameter = new Parameter('prm1', 'val1');
        $expressionVisitor->addParameter($parameter);

        self::assertEquals([$parameter], $expressionVisitor->getParameters());
    }

    public function testAddParameterWithoutType()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            $this->createMock(EntityClassResolver::class)
        );

        $expressionVisitor->addParameter('prm1', 'val1');

        self::assertEquals([new Parameter('prm1', 'val1')], $expressionVisitor->getParameters());
    }

    public function testAddParameterWithType()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            $this->createMock(EntityClassResolver::class)
        );

        $expressionVisitor->addParameter('prm1', 'val1', 'string');

        self::assertEquals([new Parameter('prm1', 'val1', 'string')], $expressionVisitor->getParameters());
    }

    public function testCreateParameter()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            $this->createMock(EntityClassResolver::class)
        );

        self::assertEquals(
            new Parameter('prm1', 'val1', 'string'),
            $expressionVisitor->createParameter('prm1', 'val1', 'string')
        );
    }

    public function testBuildPlaceholder()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            $this->createMock(EntityClassResolver::class)
        );

        self::assertEquals(':test', $expressionVisitor->buildPlaceholder('test'));
    }

    public function testGetExpressionBuilder()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            $this->createMock(EntityClassResolver::class)
        );

        self::assertInstanceOf(
            QueryExpr::class,
            $expressionVisitor->getExpressionBuilder()
        );
    }

    public function testWalkValue()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            $this->createMock(EntityClassResolver::class)
        );
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
            ['IN' => new InComparisonExpression()],
            $this->createMock(EntityClassResolver::class)
        );

        $expressionVisitor->setQueryAliases(['e']);
        $expr = new CompositeExpression('NOT SUPPORTED', [new Comparison('e.test', 'IN', [1])]);
        $expressionVisitor->walkCompositeExpression($expr);
    }

    public function testWalkCompositeExpression()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            ['AND' => new AndCompositeExpression()],
            ['=' => new EqComparisonExpression()],
            $this->createMock(EntityClassResolver::class)
        );

        $expressionVisitor->setQueryAliases(['e']);
        $expr = new CompositeExpression(
            'AND',
            [new Comparison('e.test', '=', 1), new Comparison('e.id', '=', 12), new Comparison('e.id', '=', 25)]
        );
        /** @var QueryExpr\Andx $result */
        $result = $expressionVisitor->walkCompositeExpression($expr);

        self::assertInstanceOf(QueryExpr\Andx::class, $result);
        self::assertEquals(
            [
                new QueryExpr\Comparison('e.test', '=', ':e_test'),
                new QueryExpr\Comparison('e.id', '=', ':e_id'),
                new QueryExpr\Comparison('e.id', '=', ':e_id_2')
            ],
            $result->getParts()
        );
        self::assertEquals(
            [
                new Parameter('e_test', 1, 'integer'),
                new Parameter('e_id', 12, 'integer'),
                new Parameter('e_id_2', 25, 'integer')
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
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            $this->createMock(EntityClassResolver::class)
        );

        $comparison = new Comparison('e.test', '=', 1);
        $expressionVisitor->walkComparison($comparison);
    }

    public function testWalkComparison()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            ['IN' => new InComparisonExpression()],
            $this->createMock(EntityClassResolver::class)
        );

        $expressionVisitor->setQueryAliases(['e']);
        $comparison = new Comparison('e.test', 'IN', [1, 2, 3]);
        $result = $expressionVisitor->walkComparison($comparison);

        self::assertEquals(
            new QueryExpr\Func('e.test IN', [':e_test']),
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
            ['IN' => new InComparisonExpression(), '=' => new EqComparisonExpression()],
            $this->createMock(EntityClassResolver::class)
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
            ['=' => new EqComparisonExpression()],
            $this->createMock(EntityClassResolver::class)
        );

        $expressionVisitor->setQueryAliases(['e']);
        $comparison = new Comparison('e.test', '=/a', 21);
        $expressionVisitor->walkComparison($comparison);
    }

    public function testWalkComparisonWithUnknownCaseInsensitiveModifierOfOperator()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            ['=' => new EqComparisonExpression()],
            $this->createMock(EntityClassResolver::class)
        );

        $expressionVisitor->setQueryAliases(['e']);
        $comparison = new Comparison('e.test', '=/i', 'test value');
        $result = $expressionVisitor->walkComparison($comparison);

        self::assertEquals(
            new QueryExpr\Comparison('LOWER(e.test)', '=', ':e_test'),
            $result
        );
        self::assertEquals(
            [
                new Parameter('e_test', 'test value', \PDO::PARAM_STR)
            ],
            $expressionVisitor->getParameters()
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unsafe value passed 1=1 OR e
     */
    public function testWalkComparisonWithUnsafeFieldName()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            ['=' => new EqComparisonExpression()],
            $this->createMock(EntityClassResolver::class)
        );

        $expressionVisitor->setQueryAliases(['e']);
        $comparison = new Comparison('1=1 OR e.test', '=', 'test value');
        $expressionVisitor->walkComparison($comparison);
    }

    /**
     * @param QueryBuilder $query
     * @param QueryBuilder $subquery
     *
     * @return string
     */
    private function buildExistsSql(QueryBuilder $query, QueryBuilder $subquery)
    {
        return $query
            ->andWhere($query->expr()->exists($subquery->getQuery()->getDQL()))
            ->getQuery()
            ->getSQL();
    }

    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage No query is set before invoking createSubquery().
     */
    public function testCreateSubqueryWithoutQuery()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $expressionVisitor->createSubquery('e.test');
    }

    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage No join map is set before invoking createSubquery().
     */
    public function testCreateSubqueryWithoutJoinMap()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->createSubquery('e.test');
    }

    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage No aliases are set before invoking createSubquery().
     */
    public function testCreateSubqueryWithoutAliases()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap([]);
        $expressionVisitor->createSubquery('e.test');
    }

    public function testCreateSubqueryForJoinedRootEntityAssociation()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\User::class, 'e')
            ->leftJoin('e.groups', 'groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap(['groups' => 'groups']);
        $expressionVisitor->setQueryAliases(['e', 'groups']);
        $subquery = $expressionVisitor->createSubquery('e.groups');

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups';
        self::assertEquals($expectedSubquery, $subquery->getDQL());

        // test that the subquery has valid SQL
        $expectedSql = 'SELECT u0_.id AS id_0, u0_.name AS name_1,'
            . ' u0_.category_name AS category_name_2, u0_.owner_id AS owner_id_3'
            . ' FROM user_table u0_'
            . ' LEFT JOIN user_to_group_table u2_ ON u0_.id = u2_.user_id'
            . ' LEFT JOIN group_table g1_ ON g1_.id = u2_.user_group_id'
            . ' WHERE EXISTS ('
            . 'SELECT g3_.id'
            . ' FROM group_table g3_'
            . ' WHERE g3_.id = g1_.id)';
        self::assertEquals($expectedSql, $this->buildExistsSql($qb, $subquery));
    }

    public function testCreateSubqueryForJoinedRootEntityAssociationAndEntityNameInFrom()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from('Test:User', 'e')
            ->leftJoin('e.groups', 'groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap(['groups' => 'groups']);
        $expressionVisitor->setQueryAliases(['e', 'groups']);
        $subquery = $expressionVisitor->createSubquery('e.groups');

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups';
        self::assertEquals($expectedSubquery, $subquery->getDQL());

        // test that the subquery has valid SQL
        $expectedSql = 'SELECT u0_.id AS id_0, u0_.name AS name_1,'
            . ' u0_.category_name AS category_name_2, u0_.owner_id AS owner_id_3'
            . ' FROM user_table u0_'
            . ' LEFT JOIN user_to_group_table u2_ ON u0_.id = u2_.user_id'
            . ' LEFT JOIN group_table g1_ ON g1_.id = u2_.user_group_id'
            . ' WHERE EXISTS ('
            . 'SELECT g3_.id'
            . ' FROM group_table g3_'
            . ' WHERE g3_.id = g1_.id)';
        self::assertEquals($expectedSql, $this->buildExistsSql($qb, $subquery));
    }

    public function testCreateSubqueryForJoinedAssociation()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\Origin::class, 'e')
            ->leftJoin('e.user', 'user')
            ->leftJoin('user.groups', 'user_groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap(['user' => 'user', 'user.groups' => 'user_groups']);
        $expressionVisitor->setQueryAliases(['e', 'user', 'user_groups']);
        $subquery = $expressionVisitor->createSubquery('user.groups');

        $expectedSubquery = 'SELECT user_groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group user_groups_subquery1'
            . ' WHERE user_groups_subquery1 = user_groups';
        self::assertEquals($expectedSubquery, $subquery->getDQL());

        // test that the subquery has valid SQL
        $expectedSql = 'SELECT o0_.id AS id_0, o0_.name AS name_1, o0_.user_id AS user_id_2'
            . ' FROM origin_table o0_'
            . ' LEFT JOIN user_table u1_ ON o0_.user_id = u1_.id'
            . ' LEFT JOIN user_to_group_table u3_ ON u1_.id = u3_.user_id'
            . ' LEFT JOIN group_table g2_ ON g2_.id = u3_.user_group_id'
            . ' WHERE EXISTS ('
            . 'SELECT g4_.id'
            . ' FROM group_table g4_'
            . ' WHERE g4_.id = g2_.id)';
        self::assertEquals($expectedSql, $this->buildExistsSql($qb, $subquery));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage Cannot build subquery for the field "user.groups". Reason: The join "user_groups" does not exist in the query.
     */
    // @codingStandardsIgnoreEnd
    public function testCreateSubqueryWhenJoinExistsInJoinMapButDoesNotExistInQuery()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\Origin::class, 'e')
            ->leftJoin('e.user', 'user');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap(['user' => 'user', 'user.groups' => 'user_groups']);
        $expressionVisitor->setQueryAliases(['e', 'user', 'user_groups']);
        $expressionVisitor->createSubquery('user.groups');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage Cannot build subquery for the field "user.groups". Reason: The join "user" does not exist in the query.
     */
    // @codingStandardsIgnoreEnd
    public function testCreateSubqueryWhenParentJoinExistsInJoinMapButDoesNotExistInQuery()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\Origin::class, 'e')
            ->leftJoin('user.groups', 'user_groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap(['user' => 'user', 'user.groups' => 'user_groups']);
        $expressionVisitor->setQueryAliases(['e', 'user', 'user_groups']);
        $expressionVisitor->createSubquery('user.groups');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage Cannot build subquery for the field "user.groups". Reason: The join "user" does not exist in the query.
     */
    // @codingStandardsIgnoreEnd
    public function testCreateSubqueryWhenParentJoinDoesNotExistsInBothJoinMapAndQuery()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\Origin::class, 'e')
            ->leftJoin('user.groups', 'user_groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap(['user.groups' => 'user_groups']);
        $expressionVisitor->setQueryAliases(['e', 'user_groups']);
        $expressionVisitor->createSubquery('user.groups');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage Cannot build subquery for the field "user.unknownAssociation". Reason: Association name expected, 'unknownAssociation' is not an association.
     */
    // @codingStandardsIgnoreEnd
    public function testCreateSubqueryForUnknownAssociation()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\Origin::class, 'e')
            ->leftJoin('e.user', 'user')
            ->leftJoin('user.unknownAssociation', 'unknownAssociation');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap(['user' => 'user', 'user.unknownAssociation' => 'unknownAssociation']);
        $expressionVisitor->setQueryAliases(['e', 'user', 'unknownAssociation']);
        $expressionVisitor->createSubquery('user.unknownAssociation');
    }

    public function testCreateSubqueryForJoinedUnidirectionalAssociation()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\Group::class, 'e')
            ->leftJoin(Entity\User::class, 'users', QueryExpr\Join::WITH, 'e MEMBER OF users.groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap(['users' => 'users']);
        $expressionVisitor->setQueryAliases(['e', 'users']);
        $subquery = $expressionVisitor->createSubquery('e.users');

        $expectedSubquery = 'SELECT users_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User users_subquery1'
            . ' WHERE users_subquery1 = users';
        self::assertEquals($expectedSubquery, $subquery->getDQL());

        // test that the subquery has valid SQL
        $expectedSql = 'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' LEFT JOIN user_table u1_ ON (EXISTS ('
            . 'SELECT 1'
            . ' FROM user_to_group_table u2_'
            . ' INNER JOIN group_table g3_ ON u2_.user_group_id = g3_.id'
            . ' WHERE u2_.user_id = u1_.id AND g3_.id IN (g0_.id)))'
            . ' WHERE EXISTS ('
            . 'SELECT u4_.id'
            . ' FROM user_table u4_'
            . ' WHERE u4_.id = u1_.id)';
        self::assertEquals($expectedSql, $this->buildExistsSql($qb, $subquery));
    }

    public function testCreateSubqueryForJoinedAssociationWithUnidirectionalParentJoin()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\Origin::class, 'e')
            ->leftJoin(Entity\User::class, 'user', QueryExpr\Join::WITH, 'user = e.user')
            ->leftJoin('user.groups', 'user_groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap(['user' => 'user', 'user.groups' => 'user_groups']);
        $expressionVisitor->setQueryAliases(['e', 'user', 'user_groups']);
        $subquery = $expressionVisitor->createSubquery('user.groups');

        $expectedSubquery = 'SELECT user_groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group user_groups_subquery1'
            . ' WHERE user_groups_subquery1 = user_groups';
        self::assertEquals($expectedSubquery, $subquery->getDQL());

        // test that the subquery has valid SQL
        $expectedSql = 'SELECT o0_.id AS id_0, o0_.name AS name_1, o0_.user_id AS user_id_2'
            . ' FROM origin_table o0_'
            . ' LEFT JOIN user_table u1_ ON (u1_.id = o0_.user_id)'
            . ' LEFT JOIN user_to_group_table u3_ ON u1_.id = u3_.user_id'
            . ' LEFT JOIN group_table g2_ ON g2_.id = u3_.user_group_id'
            . ' WHERE EXISTS ('
            . 'SELECT g4_.id'
            . ' FROM group_table g4_'
            . ' WHERE g4_.id = g2_.id)';
        self::assertEquals($expectedSql, $this->buildExistsSql($qb, $subquery));
    }

    public function testCreateSubqueryForJoinedUnidirectionalAssociationWithEntityNameInJoin()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from('Test:User', 'e')
            ->leftJoin(Entity\User::class, 'users', QueryExpr\Join::WITH, 'e MEMBER OF users.groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap(['users' => 'users']);
        $expressionVisitor->setQueryAliases(['e', 'users']);
        $subquery = $expressionVisitor->createSubquery('e.users');

        $expectedSubquery = 'SELECT users_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User users_subquery1'
            . ' WHERE users_subquery1 = users';
        self::assertEquals($expectedSubquery, $subquery->getDQL());

        // test that the subquery has valid SQL
        $expectedSql = 'SELECT u0_.id AS id_0, u0_.name AS name_1,'
            . ' u0_.category_name AS category_name_2, u0_.owner_id AS owner_id_3'
            . ' FROM user_table u0_'
            . ' LEFT JOIN user_table u1_ ON (EXISTS ('
            . 'SELECT 1'
            . ' FROM user_to_group_table u2_'
            . ' INNER JOIN group_table g3_ ON u2_.user_group_id = g3_.id'
            . ' WHERE u2_.user_id = u1_.id AND g3_.id IN (u0_.id)))'
            . ' WHERE EXISTS ('
            . 'SELECT u4_.id'
            . ' FROM user_table u4_'
            . ' WHERE u4_.id = u1_.id)';
        self::assertEquals($expectedSql, $this->buildExistsSql($qb, $subquery));
    }

    public function testCreateSubqueryForJoinedAssociationWithUnidirectionalParentJoinWithEntityNameInJoin()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\Origin::class, 'e')
            ->leftJoin('Test:User', 'user', QueryExpr\Join::WITH, 'user = e.user')
            ->leftJoin('user.groups', 'user_groups');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap(['user' => 'user', 'user.groups' => 'user_groups']);
        $expressionVisitor->setQueryAliases(['e', 'user', 'user_groups']);
        $subquery = $expressionVisitor->createSubquery('user.groups');

        $expectedSubquery = 'SELECT user_groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group user_groups_subquery1'
            . ' WHERE user_groups_subquery1 = user_groups';
        self::assertEquals($expectedSubquery, $subquery->getDQL());

        // test that the subquery has valid SQL
        $expectedSql = 'SELECT o0_.id AS id_0, o0_.name AS name_1, o0_.user_id AS user_id_2'
            . ' FROM origin_table o0_'
            . ' LEFT JOIN user_table u1_ ON (u1_.id = o0_.user_id)'
            . ' LEFT JOIN user_to_group_table u3_ ON u1_.id = u3_.user_id'
            . ' LEFT JOIN group_table g2_ ON g2_.id = u3_.user_group_id'
            . ' WHERE EXISTS ('
            . 'SELECT g4_.id'
            . ' FROM group_table g4_'
            . ' WHERE g4_.id = g2_.id)';
        self::assertEquals($expectedSql, $this->buildExistsSql($qb, $subquery));
    }

    public function testCreateSubqueryForNotJoinedRootEntityAssociation()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\User::class, 'e');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap([]);
        $expressionVisitor->setQueryAliases(['e']);
        $subquery = $expressionVisitor->createSubquery('e.groups');

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups';
        self::assertEquals($expectedSubquery, $subquery->getDQL());

        // test that the subquery has valid SQL
        $expectedSql = 'SELECT u0_.id AS id_0, u0_.name AS name_1,'
            . ' u0_.category_name AS category_name_2, u0_.owner_id AS owner_id_3'
            . ' FROM user_table u0_'
            . ' WHERE EXISTS ('
            . 'SELECT g1_.id'
            . ' FROM group_table g1_'
            . ' WHERE EXISTS ('
            . 'SELECT 1 FROM user_to_group_table u2_'
            . ' INNER JOIN group_table g3_ ON u2_.user_group_id = g3_.id'
            . ' WHERE u2_.user_id = u0_.id AND g3_.id IN (g1_.id)))';
        self::assertEquals($expectedSql, $this->buildExistsSql($qb, $subquery));
    }

    public function testCreateSubqueryForNotJoinedLastAssociation()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\Origin::class, 'e')
            ->leftJoin('e.user', 'user');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap(['user' => 'user']);
        $expressionVisitor->setQueryAliases(['e', 'user']);
        $subquery = $expressionVisitor->createSubquery('user.groups');

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 IN('
            . 'SELECT groups_0_subquery2'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User user_subquery2'
            . ' INNER JOIN user_subquery2.groups groups_0_subquery2'
            . ' WHERE user_subquery2 = user)';
        self::assertEquals($expectedSubquery, $subquery->getDQL());

        // test that the subquery has valid SQL
        $expectedSql = 'SELECT o0_.id AS id_0, o0_.name AS name_1, o0_.user_id AS user_id_2'
            . ' FROM origin_table o0_'
            . ' LEFT JOIN user_table u1_ ON o0_.user_id = u1_.id'
            . ' WHERE EXISTS ('
            . 'SELECT g2_.id'
            . ' FROM group_table g2_'
            . ' WHERE g2_.id IN ('
            . 'SELECT g3_.id'
            . ' FROM user_table u4_'
            . ' INNER JOIN user_to_group_table u5_ ON u4_.id = u5_.user_id'
            . ' INNER JOIN group_table g3_ ON g3_.id = u5_.user_group_id'
            . ' WHERE u4_.id = u1_.id))';
        self::assertEquals($expectedSql, $this->buildExistsSql($qb, $subquery));
    }

    public function testCreateSubqueryForNotJoinedAllAssociations()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\Origin::class, 'e');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap([]);
        $expressionVisitor->setQueryAliases(['e']);
        $subquery = $expressionVisitor->createSubquery('user.groups');

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 IN('
            . 'SELECT groups_1_subquery2'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Origin e_subquery2'
            . ' INNER JOIN e_subquery2.user user_0_subquery2'
            . ' INNER JOIN user_0_subquery2.groups groups_1_subquery2'
            . ' WHERE e_subquery2 = e)';
        self::assertEquals($expectedSubquery, $subquery->getDQL());

        // test that the subquery has valid SQL
        $expectedSql = 'SELECT o0_.id AS id_0, o0_.name AS name_1, o0_.user_id AS user_id_2'
            . ' FROM origin_table o0_'
            . ' WHERE EXISTS ('
            . 'SELECT g1_.id'
            . ' FROM group_table g1_'
            . ' WHERE g1_.id IN ('
            . 'SELECT g2_.id'
            . ' FROM origin_table o3_'
            . ' INNER JOIN user_table u4_ ON o3_.user_id = u4_.id'
            . ' INNER JOIN user_to_group_table u5_ ON u4_.id = u5_.user_id'
            . ' INNER JOIN group_table g2_ ON g2_.id = u5_.user_group_id'
            . ' WHERE o3_.id = o0_.id))';
        self::assertEquals($expectedSql, $this->buildExistsSql($qb, $subquery));
    }

    public function testCreateSubqueryForNotJoinedAllAssociationsAndPathHasMoreThanTwoElements()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\Account::class, 'e');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap([]);
        $expressionVisitor->setQueryAliases(['e']);
        $subquery = $expressionVisitor->createSubquery('roles.users.groups');

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 IN('
            . 'SELECT groups_2_subquery2'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account e_subquery2'
            . ' INNER JOIN e_subquery2.roles roles_0_subquery2'
            . ' INNER JOIN roles_0_subquery2.users users_1_subquery2'
            . ' INNER JOIN users_1_subquery2.groups groups_2_subquery2'
            . ' WHERE e_subquery2 = e)';
        self::assertEquals($expectedSubquery, $subquery->getDQL());

        // test that the subquery has valid SQL
        $expectedSql = 'SELECT a0_.id AS id_0, a0_.name AS name_1'
            . ' FROM account_table a0_'
            . ' WHERE EXISTS ('
            . 'SELECT g1_.id'
            . ' FROM group_table g1_'
            . ' WHERE g1_.id IN ('
            . 'SELECT g2_.id'
            . ' FROM account_table a3_'
            . ' INNER JOIN account_to_role_table a5_ ON a3_.id = a5_.group_id'
            . ' INNER JOIN role_table r4_ ON r4_.id = a5_.role_id'
            . ' INNER JOIN role_to_user_table r7_ ON r4_.id = r7_.role_id'
            . ' INNER JOIN user_table u6_ ON u6_.id = r7_.user_role_id'
            . ' INNER JOIN user_to_group_table u8_ ON u6_.id = u8_.user_id'
            . ' INNER JOIN group_table g2_ ON g2_.id = u8_.user_group_id'
            . ' WHERE a3_.id = a0_.id))';
        self::assertEquals($expectedSql, $this->buildExistsSql($qb, $subquery));
    }

    public function testCreateSubqueryForNotJoinedSeveralAssociationsAndPathHasMoreThanTwoElements()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\Account::class, 'e')
            ->leftJoin('e.roles', 'roles');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap(['roles' => 'roles']);
        $expressionVisitor->setQueryAliases(['e', 'roles']);
        $subquery = $expressionVisitor->createSubquery('roles.users.groups');

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 IN('
            . 'SELECT groups_1_subquery2'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Role roles_subquery2'
            . ' INNER JOIN roles_subquery2.users users_0_subquery2'
            . ' INNER JOIN users_0_subquery2.groups groups_1_subquery2'
            . ' WHERE roles_subquery2 = roles)';
        self::assertEquals($expectedSubquery, $subquery->getDQL());

        // test that the subquery has valid SQL
        $expectedSql = 'SELECT a0_.id AS id_0, a0_.name AS name_1'
            . ' FROM account_table a0_'
            . ' LEFT JOIN account_to_role_table a2_ ON a0_.id = a2_.group_id'
            . ' LEFT JOIN role_table r1_ ON r1_.id = a2_.role_id'
            . ' WHERE EXISTS ('
            . 'SELECT g3_.id'
            . ' FROM group_table g3_'
            . ' WHERE g3_.id IN ('
            . 'SELECT g4_.id'
            . ' FROM role_table r5_'
            . ' INNER JOIN role_to_user_table r7_ ON r5_.id = r7_.role_id'
            . ' INNER JOIN user_table u6_ ON u6_.id = r7_.user_role_id'
            . ' INNER JOIN user_to_group_table u8_ ON u6_.id = u8_.user_id'
            . ' INNER JOIN group_table g4_ ON g4_.id = u8_.user_group_id'
            . ' WHERE r5_.id = r1_.id))';
        self::assertEquals($expectedSql, $this->buildExistsSql($qb, $subquery));
    }

    public function testCreateSubqueryForNotJoinedLastAssociationsAndPathHasMoreThanTwoElements()
    {
        $expressionVisitor = new QueryExpressionVisitor(
            [],
            [],
            new EntityClassResolver($this->doctrine)
        );

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\Account::class, 'e')
            ->leftJoin('e.roles', 'roles')
            ->leftJoin('roles.users', 'users');

        $expressionVisitor->setQuery($qb);
        $expressionVisitor->setQueryJoinMap(['roles' => 'roles', 'roles.users' => 'users']);
        $expressionVisitor->setQueryAliases(['e', 'roles', 'users']);
        $subquery = $expressionVisitor->createSubquery('roles.users.groups');

        $expectedSubquery = 'SELECT groups_subquery1'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group groups_subquery1'
            . ' WHERE groups_subquery1 IN('
            . 'SELECT groups_0_subquery2'
            . ' FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User users_subquery2'
            . ' INNER JOIN users_subquery2.groups groups_0_subquery2'
            . ' WHERE users_subquery2 = users)';
        self::assertEquals($expectedSubquery, $subquery->getDQL());

        // test that the subquery has valid SQL
        $expectedSql = 'SELECT a0_.id AS id_0, a0_.name AS name_1'
            . ' FROM account_table a0_'
            . ' LEFT JOIN account_to_role_table a2_ ON a0_.id = a2_.group_id'
            . ' LEFT JOIN role_table r1_ ON r1_.id = a2_.role_id'
            . ' LEFT JOIN role_to_user_table r4_ ON r1_.id = r4_.role_id'
            . ' LEFT JOIN user_table u3_ ON u3_.id = r4_.user_role_id'
            . ' WHERE EXISTS ('
            . 'SELECT g5_.id'
            . ' FROM group_table g5_'
            . ' WHERE g5_.id IN ('
            . 'SELECT g6_.id'
            . ' FROM user_table u7_'
            . ' INNER JOIN user_to_group_table u8_ ON u7_.id = u8_.user_id'
            . ' INNER JOIN group_table g6_ ON g6_.id = u8_.user_group_id'
            . ' WHERE u7_.id = u3_.id))';
        self::assertEquals($expectedSql, $this->buildExistsSql($qb, $subquery));
    }
}
