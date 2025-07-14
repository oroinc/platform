<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Query\Expr\Join;
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
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class QueryBuilderUtilTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AttributeDriver([]));
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
     */
    public function testGetPageOffset(int $expectedOffset, int|string|null $page, int|string|null $limit): void
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

    public function testNormalizeNullCriteria(): void
    {
        $this->assertEquals(
            new Criteria(),
            QueryBuilderUtil::normalizeCriteria(null)
        );
    }

    public function testNormalizeEmptyCriteria(): void
    {
        $this->assertEquals(
            new Criteria(),
            QueryBuilderUtil::normalizeCriteria([])
        );
    }

    public function testNormalizeCriteriaObject(): void
    {
        $criteria = new Criteria();
        $this->assertSame(
            $criteria,
            QueryBuilderUtil::normalizeCriteria($criteria)
        );
    }

    public function testNormalizeCriteriaArray(): void
    {
        $expectedCriteria = new Criteria();
        $expectedCriteria->andWhere(Criteria::expr()->eq('field', 'value'));

        $this->assertEquals(
            $expectedCriteria,
            QueryBuilderUtil::normalizeCriteria(['field' => 'value'])
        );
    }

    public function testNormalizeCriteriaArrayValue(): void
    {
        $expectedCriteria = new Criteria();
        $expectedCriteria->andWhere(Criteria::expr()->in('field', ['value']));

        $this->assertEquals(
            $expectedCriteria,
            QueryBuilderUtil::normalizeCriteria(['field' => ['value']])
        );
    }

    /**
     * @dataProvider getSelectExprProvider
     */
    public function testGetSelectExpr(QueryBuilder $qb, string $expectedExpr): void
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
    public function testGetSelectExprByAlias(QueryBuilder $qb, string $alias, ?string $expectedExpr): void
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

    public function testGetSingleRootAlias(): void
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

    public function testGetSingleRootAliasWhenQueryHasSeveralRootAliases(): void
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

    public function testGetSingleRootAliasWhenQueryHasNoRootAlias(): void
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

    public function testGetSingleRootAliasWhenQueryHasNoRootAliasAndNoExceptionRequested(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn([]);

        $this->assertNull(QueryBuilderUtil::getSingleRootAlias($qb, false));
    }

    public function testGetSingleRootEntity(): void
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

    public function testGetSingleRootEntityWhenQueryHasSeveralRootEntities(): void
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

    public function testGetSingleRootEntityWhenQueryHasNoRootEntity(): void
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

    public function testGetSingleRootEntityWhenQueryHasNoRootEntityAndNoExceptionRequested(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('getRootEntities')
            ->willReturn([]);

        $this->assertNull(QueryBuilderUtil::getSingleRootEntity($qb, false));
    }

    public function testApplyEmptyJoins(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->never())
            ->method('distinct');
        $qb->expects($this->never())
            ->method('getRootAliases');

        QueryBuilderUtil::applyJoins($qb, []);
    }

    public function testApplyJoins(): void
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

    public function testGenerateParameterNameForEmptyPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::generateParameterName('', $this->createMock(QueryBuilder::class));
    }

    public function testGenerateParameterNameForInvalidPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::generateParameterName('!@#', $this->createMock(QueryBuilder::class));
    }

    public function testGenerateParameterNameWhenQueryBuilderDoesNotHaveParameterWithSpecifiedName(): void
    {
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        self::assertEquals('param', QueryBuilderUtil::generateParameterName('param', $qb));
    }

    public function testGenerateParameterNameWhenQueryBuilderHasParameterWithSpecifiedName(): void
    {
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));
        $qb->setParameter('param', 1, 'int');

        self::assertEquals('param1', QueryBuilderUtil::generateParameterName('param', $qb));
    }

    public function testGenerateParameterNameWhenQueryBuilderHasSeveralParametersWithSpecifiedName(): void
    {
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));
        $qb->setParameter('param', 1, 'int');
        $qb->setParameter('param1', 1, 'int');
        $qb->setParameter('param2', 1, 'int');

        self::assertEquals('param3', QueryBuilderUtil::generateParameterName('param', $qb));
    }

    public function testRemoveUnusedParameters(): void
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
            ->willReturn(new ArrayCollection($parameters));
        $qb->expects($this->once())
            ->method('setParameters')
            ->with($expectedParameters);

        QueryBuilderUtil::removeUnusedParameters($qb);
    }

    public function testFindClassForRootAlias(): void
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Person::class, 'p')
            ->join('p.bestItem', 'i');

        $this->assertEquals(
            Person::class,
            QueryBuilderUtil::findClassByAlias($qb, 'p')
        );
    }

    public function testFindClassForRootAliasFowQueryWithSeveralRootEntities(): void
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Person::class, 'p')
            ->from(Group::class, 'g')
            ->join('p.bestItem', 'i');

        $this->assertEquals(
            Group::class,
            QueryBuilderUtil::findClassByAlias($qb, 'g')
        );
    }

    public function testFindClassWhenAliasNotFound(): void
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Person::class, 'p')
            ->join('p.bestItem', 'i');

        $this->assertNull(
            QueryBuilderUtil::findClassByAlias($qb, 'another')
        );
    }

    /**
     * @dataProvider getJoinClassDataProvider
     */
    public function testFindClassByAliasForJoinAlias(callable $qbFactory, array $joinPath, string $expectedClass): void
    {
        $qb = $qbFactory($this->em);

        $this->assertEquals(
            $expectedClass,
            QueryBuilderUtil::findClassByAlias($qb, ArrayUtil::getIn($qb->getDqlPart('join'), $joinPath)->getAlias())
        );
    }

    /**
     * @dataProvider getJoinClassDataProvider
     */
    public function testGetJoinClass(callable $qbFactory, array $joinPath, string $expectedClass): void
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

    public function testFindJoinByAlias(): void
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

    public function testAddJoin(): void
    {
        $srcQb = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Person::class, 'p')
            ->innerJoin(Group::class, 'g', Join::WITH, 'g MEMBER OF p.groups')
            ->leftJoin(Item::class, 'i', Join::WITH, 'i MEMBER OF p.items');

        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Person::class, 'p');

        QueryBuilderUtil::addJoin($qb, QueryBuilderUtil::findJoinByAlias($srcQb, 'g'));
        QueryBuilderUtil::addJoin($qb, QueryBuilderUtil::findJoinByAlias($srcQb, 'i'));

        $this->assertEquals(
            'SELECT p'
            . ' FROM Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Person p'
            . ' INNER JOIN Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Group g WITH g MEMBER OF p.groups'
            . ' LEFT JOIN Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Item i WITH i MEMBER OF p.items',
            $qb->getDQL()
        );
    }

    public function testIsToOne(): void
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

    public function testSprintfValid(): void
    {
        $this->assertEquals('tesT.One_1 > :param', QueryBuilderUtil::sprintf('%s.%s > :param', 'tesT', 'One_1'));
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testSprintfInvalid(string $invalid): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::sprintf('%s.%s > 0', $invalid, 'id');
    }

    public function testCheckIdentifierValid(): void
    {
        QueryBuilderUtil::checkIdentifier('tEs_T_01a');
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testCheckStringInvalid(string $invalid): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkIdentifier($invalid);
    }

    public function testCheckFieldForValidFieldWithoutAlias(): void
    {
        QueryBuilderUtil::checkField('tEs_T_01a');
    }

    public function testCheckFieldForValidFieldWithAlias(): void
    {
        QueryBuilderUtil::checkField('tEs_T_01a.tEs_T_01a');
    }

    public function testCheckFieldForInvalidFieldWithoutAlias(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkField('0_some//');
    }

    public function testCheckFieldForInvalidAliasPart(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkField('0_some//.field');
    }

    public function testCheckFieldForInvalidFieldPart(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkField('alias.0_some//');
    }

    public function testCheckPathForValidFieldWithoutAlias(): void
    {
        QueryBuilderUtil::checkPath('tEs_T_01a');
    }

    public function testCheckPathForValidFieldWithAlias(): void
    {
        QueryBuilderUtil::checkPath('tEs_T_01a.tEs_T_01a');
    }

    public function testCheckPathForValidNestedField(): void
    {
        QueryBuilderUtil::checkPath('tEs_T_01a.tEs_T_01a.tEs_T_01a');
    }

    public function testCheckPathForInvalidFieldWithoutAlias(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkPath('0_some//');
    }

    public function testCheckPathForInvalidAliasPart(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkPath('0_some//.field');
    }

    public function testCheckPathForInvalidFieldPart(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkPath('alias.0_some//');
    }

    public function testCheckPathForInvalidNestedFieldPart(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QueryBuilderUtil::checkPath('alias.field.0_some//');
    }

    public function testGetFieldValid(): void
    {
        $this->assertEquals('a0_.Field0', QueryBuilderUtil::getField('a0_', 'Field0'));
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testGetFieldInvalid(string $invalid): void
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
