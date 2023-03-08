<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Group;
use Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Item;
use Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Person;
use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class QueryBuilderUtilTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->createMock(EntityManagerInterface::class));
    }

    private function getParameter(string|int $name): Parameter
    {
        $parameter = $this->createMock(Parameter::class);
        $parameter->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $parameter->expects($this->any())
            ->method('getValue')
            ->willReturn($name . '_value');

        return $parameter;
    }

    /**
     * @dataProvider getPageOffsetProvider
     * @param int $expectedOffset
     * @param null|int|string $page
     * @param null|int|string $limit
     */
    public function testGetPageOffset($expectedOffset, $page, $limit)
    {
        $this->assertSame($expectedOffset, QueryBuilderUtil::getPageOffset($page, $limit));
    }

    public function getPageOffsetProvider(): array
    {
        return [
            [0, null, null],
            [0, null, 5],
            [0, 2, null],
            [0, 1, 5],
            [5, 2, 5],
            [0, '2', null],
            [0, '1', '5'],
            [5, '2', '5']
        ];
    }

    public function testNormalizeNullCriteria()
    {
        $this->assertEquals(
            new Criteria(),
            QueryBuilderUtil::normalizeCriteria(null)
        );
    }

    public function testNormalizeEmptyCriteria()
    {
        $this->assertEquals(
            new Criteria(),
            QueryBuilderUtil::normalizeCriteria([])
        );
    }

    public function testNormalizeCriteriaObject()
    {
        $criteria = new Criteria();
        $this->assertSame(
            $criteria,
            QueryBuilderUtil::normalizeCriteria($criteria)
        );
    }

    public function testNormalizeCriteriaArray()
    {
        $expectedCriteria = new Criteria();
        $expectedCriteria->andWhere(Criteria::expr()->eq('field', 'value'));

        $this->assertEquals(
            $expectedCriteria,
            QueryBuilderUtil::normalizeCriteria(['field' => 'value'])
        );
    }

    /**
     * @dataProvider getSelectExprProvider
     */
    public function testGetSelectExpr(QueryBuilder $qb, string $expectedExpr)
    {
        $this->assertEquals($expectedExpr, QueryBuilderUtil::getSelectExpr($qb));
    }

    public function getSelectExprProvider(): array
    {
        return [
            [
                $this->getQueryBuilder()->select('e'),
                'e'
            ],
            [
                $this->getQueryBuilder()->select('e, a'),
                'e, a'
            ],
            [
                $this->getQueryBuilder()->addSelect('e')->addSelect('a'),
                'e, a'
            ],
            [
                $this->getQueryBuilder()->select('e.id'),
                'e.id'
            ],
            [
                $this->getQueryBuilder()->select('e.id as id'),
                'e.id as id'
            ],
            [
                $this->getQueryBuilder()->select('e.id as id, e.name AS name1'),
                'e.id as id, e.name AS name1'
            ],
            [
                $this->getQueryBuilder()->select('e.id as id, e.name AS name1')->addSelect('e.lbl AS name2'),
                'e.id as id, e.name AS name1, e.lbl AS name2'
            ],
            [
                $this->getQueryBuilder()->select('e.id, CONCAT(e.name1, e.name2) AS name'),
                'e.id, CONCAT(e.name1, e.name2) AS name'
            ]
        ];
    }

    /**
     * @dataProvider getSelectExprByAliasProvider
     */
    public function testGetSelectExprByAlias(QueryBuilder $qb, string $alias, ?string $expectedExpr)
    {
        $this->assertEquals($expectedExpr, QueryBuilderUtil::getSelectExprByAlias($qb, $alias));
    }

    public function getSelectExprByAliasProvider(): array
    {
        return [
            [
                $this->getQueryBuilder()->select('e'),
                'test',
                null
            ],
            [
                $this->getQueryBuilder()->select('e.id as id'),
                'id',
                'e.id'
            ],
            [
                $this->getQueryBuilder()->select('e.id as id, e.name AS name1'),
                'id',
                'e.id'
            ],
            [
                $this->getQueryBuilder()->select('e.id as id, e.name AS name1'),
                'name1',
                'e.name'
            ],
            [
                $this->getQueryBuilder()->select('e.id as id, e.name AS name1')->addSelect('e.lbl AS name2'),
                'name2',
                'e.lbl'
            ],
            [
                $this->getQueryBuilder()->select('e.id as id, CONCAT(e.name1, e.name2) AS name'),
                'name',
                'CONCAT(e.name1, e.name2)'
            ],
        ];
    }

    public function testGetSingleRootAlias()
    {
        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['root_alias']);

        $this->assertEquals(
            'root_alias',
            QueryBuilderUtil::getSingleRootAlias($qb)
        );
    }

    public function testGetSingleRootAliasWhenQueryHasSeveralRootAliases()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage(
            'Can\'t get single root alias for the given query.'
            . ' Reason: the query has several root aliases: root_alias1, root_alias1.'
        );

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['root_alias1', 'root_alias1']);

        QueryBuilderUtil::getSingleRootAlias($qb);
    }

    public function testGetSingleRootAliasWhenQueryHasNoRootAlias()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage(
            'Can\'t get single root alias for the given query. Reason: the query has no any root aliases.'
        );

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn([]);

        QueryBuilderUtil::getSingleRootAlias($qb);
    }

    public function testGetSingleRootAliasWhenQueryHasNoRootAliasAndNoExceptionRequested()
    {
        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn([]);

        $this->assertNull(QueryBuilderUtil::getSingleRootAlias($qb, false));
    }

    public function testGetSingleRootEntity()
    {
        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('getRootEntities')
            ->willReturn(['Test\Entity']);

        $this->assertEquals(
            'Test\Entity',
            QueryBuilderUtil::getSingleRootEntity($qb)
        );
    }

    public function testGetSingleRootEntityWhenQueryHasSeveralRootEntities()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage(
            'Can\'t get single root entity for the given query.'
            . ' Reason: the query has several root entities: Test\Entity1, Test\Entity1.'
        );

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('getRootEntities')
            ->willReturn(['Test\Entity1', 'Test\Entity1']);

        QueryBuilderUtil::getSingleRootEntity($qb);
    }

    public function testGetSingleRootEntityWhenQueryHasNoRootEntity()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage(
            'Can\'t get single root entity for the given query. Reason: the query has no any root entities.'
        );

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('getRootEntities')
            ->willReturn([]);

        QueryBuilderUtil::getSingleRootEntity($qb);
    }

    public function testGetSingleRootEntityWhenQueryHasNoRootEntityAndNoExceptionRequested()
    {
        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('getRootEntities')
            ->willReturn([]);

        $this->assertNull(QueryBuilderUtil::getSingleRootEntity($qb, false));
    }

    public function testApplyEmptyJoins()
    {
        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->never())
            ->method('distinct');
        $qb->expects($this->never())
            ->method('getRootAliases');

        QueryBuilderUtil::applyJoins($qb, []);
    }

    public function testApplyJoins()
    {
        $joins = [
            'emails'   => null,
            'phones',
            'contacts' => [],
            'accounts' => [
                'join' => 'accounts_field'
            ],
            'users'    => [
                'join'          => 'accounts.users_field',
                'condition'     => 'users.active = true',
                'conditionType' => 'WITH'
            ],
            'products'    => [
                'condition' => 'products.active = true'
            ]
        ];

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('distinct')
            ->with(true);
        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['root_alias']);
        $qb->expects($this->exactly(6))
            ->method('leftJoin')
            ->withConsecutive(
                ['root_alias.emails', 'emails'],
                ['root_alias.phones', 'phones'],
                ['root_alias.contacts', 'contacts'],
                ['root_alias.accounts_field', 'accounts'],
                ['accounts.users_field', 'users', 'WITH', 'users.active = true'],
                ['root_alias.products', 'products', 'WITH', 'products.active = true']
            );

        QueryBuilderUtil::applyJoins($qb, $joins);
    }

    public function testFixUnusedParameters()
    {
        $dql = 'SELECT a.name FROM Some:Other as a WHERE a.name = :param1
                AND a.name != :param2 AND a.status = ?1';
        $parameters = [
            $this->getParameter(0),
            $this->getParameter(1),
            $this->getParameter('param1'),
            $this->getParameter('param2'),
            $this->getParameter('param3'),
        ];
        $expectedParameters = [
            1 => '1_value',
            'param1' => 'param1_value',
            'param2' => 'param2_value',
        ];

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getDql')
            ->willReturn($dql);
        $qb->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);
        $qb->expects($this->once())
            ->method('setParameters')
            ->with($expectedParameters);

        QueryBuilderUtil::removeUnusedParameters($qb);
    }

    /**
     * @dataProvider getJoinClassDataProvider
     */
    public function testGetJoinClass(callable $qbFactory, array $joinPath, string $expectedClass)
    {
        $qb = $qbFactory($this->em);

        $this->assertEquals(
            $expectedClass,
            QueryBuilderUtil::getJoinClass($qb, ArrayUtil::getIn($qb->getDqlPart('join'), $joinPath))
        );
    }

    public function getJoinClassDataProvider(): array
    {
        return [
            'field:manyToOne' => [
                function (EntityManagerInterface $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from(Person::class, 'p')
                        ->join('p.bestItem', 'i');
                },
                ['p', 0],
                Item::class,
            ],
            'field:manyToMany' => [
                function (EntityManagerInterface $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from(Person::class, 'p')
                        ->join('p.groups', 'g');
                },
                ['p', 0],
                Group::class,
            ],
            'field:manyToMany.field:manyToMany' => [
                function (EntityManagerInterface $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from(Person::class, 'p')
                        ->join('p.groups', 'g')
                        ->join('g.items', 'i');
                },
                ['p', 1],
                Item::class,
            ],
            'class:manyToOne' => [
                function (EntityManagerInterface $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from(Person::class, 'p')
                        ->join(Item::class, 'i');
                },
                ['p', 0],
                Item::class,
            ],
            'class:manyToMany' => [
                function (EntityManagerInterface $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from(Person::class, 'p')
                        ->join(Group::class, 'g');
                },
                ['p', 0],
                Group::class,
            ],
            'class:manyToMany.class:manyToMany' => [
                function (EntityManagerInterface $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from(Person::class, 'p')
                        ->join(Group::class, 'g')
                        ->join(Item::class, 'i');
                },
                ['p', 1],
                Item::class,
            ],
        ];
    }

    public function testFindJoinByAlias()
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Person::class, 'p')
            ->join(Group::class, 'g')
            ->join(Item::class, 'i');

        $this->assertNull(QueryBuilderUtil::findJoinByAlias($qb, 'p'));
        $this->assertEquals('g', QueryBuilderUtil::findJoinByAlias($qb, 'g')->getAlias());
        $this->assertEquals('i', QueryBuilderUtil::findJoinByAlias($qb, 'i')->getAlias());
        $this->assertNull(QueryBuilderUtil::findJoinByAlias($qb, 'w'));
    }

    public function testIsToOne()
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Person::class, 'p')
            ->join('p.bestItem', 'i')
            ->join('i.owner', 'o')
            ->join('i.persons', 'persons')
            ->join('persons.bestItem', 'bi');

        $this->assertTrue(QueryBuilderUtil::isToOne($qb, 'i'));
        $this->assertTrue(QueryBuilderUtil::isToOne($qb, 'o'));
        $this->assertFalse(QueryBuilderUtil::isToOne($qb, 'bi'));
        $this->assertFalse(QueryBuilderUtil::isToOne($qb, 'persons'));
        $this->assertFalse(QueryBuilderUtil::isToOne($qb, 'nonExistingAlias'));
    }

    public function testSprintfValid()
    {
        $this->assertEquals('tesT.One_1 > :param', QueryBuilderUtil::sprintf('%s.%s > :param', 'tesT', 'One_1'));
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testSprintfInvalid(string $invalid)
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::sprintf('%s.%s > 0', $invalid, 'id');
    }

    public function testCheckIdentifierValid()
    {
        QueryBuilderUtil::checkIdentifier('tEs_T_01a');
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testCheckStringInvalid(string $invalid)
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkIdentifier($invalid);
    }

    public function testCheckFieldForValidFieldWithoutAlias()
    {
        QueryBuilderUtil::checkField('tEs_T_01a');
    }

    public function testCheckFieldForValidFieldWithAlias()
    {
        QueryBuilderUtil::checkField('tEs_T_01a.tEs_T_01a');
    }

    public function testCheckFieldForInvalidFieldWithoutAlias()
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkField('0_some//');
    }

    public function testCheckFieldForInvalidAliasPart()
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkField('0_some//.field');
    }

    public function testCheckFieldForInvalidFieldPart()
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkField('alias.0_some//');
    }

    public function testCheckPathForValidFieldWithoutAlias()
    {
        QueryBuilderUtil::checkPath('tEs_T_01a');
    }

    public function testCheckPathForValidFieldWithAlias()
    {
        QueryBuilderUtil::checkPath('tEs_T_01a.tEs_T_01a');
    }

    public function testCheckPathForValidNestedField()
    {
        QueryBuilderUtil::checkPath('tEs_T_01a.tEs_T_01a.tEs_T_01a');
    }

    public function testCheckPathForInvalidFieldWithoutAlias()
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkPath('0_some//');
    }

    public function testCheckPathForInvalidAliasPart()
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkPath('0_some//.field');
    }

    public function testCheckPathForInvalidFieldPart()
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkPath('alias.0_some//');
    }

    public function testCheckPathForInvalidNestedFieldPart()
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkPath('alias.field.0_some//');
    }

    public function testGetFieldValid()
    {
        $this->assertEquals('a0_.Field0', QueryBuilderUtil::getField('a0_', 'Field0'));
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testGetFieldInvalid(string $invalid)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertEquals('a0_.Field0', QueryBuilderUtil::getField('a0_', $invalid));
    }

    public function invalidDataProvider(): array
    {
        return [
            ['test OR u.id < 0'],
            ['test" and '],
            ['0_some//']
        ];
    }
}
