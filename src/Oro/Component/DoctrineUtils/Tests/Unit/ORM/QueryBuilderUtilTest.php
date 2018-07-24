<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Component\TestUtils\ORM\OrmTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QueryBuilderUtilTest extends OrmTestCase
{
    /** @var EntityManager */
    protected $em;

    public function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'Test' => 'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity'
            ]
        );
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return new QueryBuilder($this->createMock(EntityManager::class));
    }

    /**
     * @param string $name
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getParameterMock($name)
    {
        $parameter = $this->getMockBuilder('\Doctrine\ORM\Query\Parameter')
            ->disableOriginalConstructor()
            ->getMock();
        $parameter->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        $parameter->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue($name . '_value'));

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

    /**
     * @return array
     */
    public function getPageOffsetProvider()
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
     * @dataProvider getSelectExprByAliasProvider
     *
     * @param QueryBuilder $qb
     * @param string       $alias
     * @param string       $expectedExpr
     */
    public function testGetSelectExprByAlias($qb, $alias, $expectedExpr)
    {
        $this->assertEquals($expectedExpr, QueryBuilderUtil::getSelectExprByAlias($qb, $alias));
    }

    /**
     * @return array
     */
    public function getSelectExprByAliasProvider()
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
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['root_alias']);

        $this->assertEquals(
            'root_alias',
            QueryBuilderUtil::getSingleRootAlias($qb)
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage Can't get single root alias for the given query. Reason: the query has several root aliases. "root_alias1, root_alias1".
     */
    // @codingStandardsIgnoreEnd
    public function testGetSingleRootAliasWhenQueryHasSeveralRootAliases()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['root_alias1', 'root_alias1']);

        QueryBuilderUtil::getSingleRootAlias($qb);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage Can't get single root alias for the given query. Reason: the query has no any root aliases.
     */
    // @codingStandardsIgnoreEnd
    public function testGetSingleRootAliasWhenQueryHasNoRootAlias()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn([]);

        QueryBuilderUtil::getSingleRootAlias($qb);
    }

    public function testGetSingleRootAliasWhenQueryHasNoRootAliasAndNoExceptionRequested()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn([]);

        $this->assertNull(QueryBuilderUtil::getSingleRootAlias($qb, false));
    }

    public function testApplyEmptyJoins()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

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

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $qb->expects($this->once())
            ->method('distinct')
            ->with(true);
        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['root_alias']);
        $qb->expects($this->at(2))
            ->method('leftJoin')
            ->with('root_alias.emails', 'emails');
        $qb->expects($this->at(3))
            ->method('leftJoin')
            ->with('root_alias.phones', 'phones');
        $qb->expects($this->at(4))
            ->method('leftJoin')
            ->with('root_alias.contacts', 'contacts');
        $qb->expects($this->at(5))
            ->method('leftJoin')
            ->with('root_alias.accounts_field', 'accounts');
        $qb->expects($this->at(6))
            ->method('leftJoin')
            ->with(
                'accounts.users_field',
                'users',
                'WITH',
                'users.active = true'
            );
        $qb->expects($this->at(7))
            ->method('leftJoin')
            ->with(
                'root_alias.products',
                'products',
                'WITH',
                'products.active = true'
            );

        QueryBuilderUtil::applyJoins($qb, $joins);
    }

    public function testFixUnusedParameters()
    {
        $dql = 'SELECT a.name FROM Some:Other as a WHERE a.name = :param1
                AND a.name != :param2 AND a.status = ?1';
        $parameters = [
            $this->getParameterMock(0),
            $this->getParameterMock(1),
            $this->getParameterMock('param1'),
            $this->getParameterMock('param2'),
            $this->getParameterMock('param3'),
        ];
        $expectedParameters = [
            1 => '1_value',
            'param1' => 'param1_value',
            'param2' => 'param2_value',
        ];

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('getDql')
            ->will($this->returnValue($dql));
        $qb->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));
        $qb->expects($this->once())
            ->method('setParameters')
            ->with($expectedParameters);

        QueryBuilderUtil::removeUnusedParameters($qb);
    }

    /**
     * @dataProvider getJoinClassDataProvider
     * @param callable $qbFactory
     * @param array $joinPath
     * @param string $expectedClass
     */
    public function testGetJoinClass(callable $qbFactory, $joinPath, $expectedClass)
    {
        $qb = $qbFactory($this->em);

        $this->assertEquals(
            $expectedClass,
            QueryBuilderUtil::getJoinClass($qb, ArrayUtil::getIn($qb->getDqlPart('join'), $joinPath))
        );
    }

    /**
     * @return array
     */
    public function getJoinClassDataProvider()
    {
        return [
            'field:manyToOne' => [
                function (EntityManager $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Person', 'p')
                        ->join('p.bestItem', 'i');
                },
                ['p', 0],
                'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Item',
            ],
            'field:manyToMany' => [
                function (EntityManager $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Person', 'p')
                        ->join('p.groups', 'g');
                },
                ['p', 0],
                'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Group',
            ],
            'field:manyToMany.field:manyToMany' => [
                function (EntityManager $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Person', 'p')
                        ->join('p.groups', 'g')
                        ->join('g.items', 'i');
                },
                ['p', 1],
                'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Item',
            ],
            'class:manyToOne' => [
                function (EntityManager $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Person', 'p')
                        ->join('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Item', 'i');
                },
                ['p', 0],
                'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Item',
            ],
            'class:manyToMany' => [
                function (EntityManager $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Person', 'p')
                        ->join('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Group', 'g');
                },
                ['p', 0],
                'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Group',
            ],
            'class:manyToMany.class:manyToMany' => [
                function (EntityManager $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Person', 'p')
                        ->join('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Group', 'g')
                        ->join('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Item', 'i');
                },
                ['p', 1],
                'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Item',
            ],
        ];
    }

    public function testFindJoinByAlias()
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Person', 'p')
            ->join('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Group', 'g')
            ->join('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Item', 'i');

        $this->assertNull(QueryBuilderUtil::findJoinByAlias($qb, 'p'));
        $this->assertEquals('g', QueryBuilderUtil::findJoinByAlias($qb, 'g')->getAlias());
        $this->assertEquals('i', QueryBuilderUtil::findJoinByAlias($qb, 'i')->getAlias());
        $this->assertNull(QueryBuilderUtil::findJoinByAlias($qb, 'w'));
    }

    public function testIsToOne()
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Person', 'p')
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
     * @param string $invalid
     */
    public function testSprintfInvalid($invalid)
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
     * @param string $invalid
     */
    public function testCheckStringInvalid($invalid)
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCheckFieldForInvalidFieldWithoutAlias()
    {
        QueryBuilderUtil::checkField('0_some//');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCheckFieldForInvalidAliasPart()
    {
        QueryBuilderUtil::checkField('0_some//.field');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCheckFieldForInvalidFieldPart()
    {
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCheckPathForInvalidFieldWithoutAlias()
    {
        QueryBuilderUtil::checkPath('0_some//');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCheckPathForInvalidAliasPart()
    {
        QueryBuilderUtil::checkPath('0_some//.field');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCheckPathForInvalidFieldPart()
    {
        QueryBuilderUtil::checkPath('alias.0_some//');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCheckPathForInvalidNestedFieldPart()
    {
        QueryBuilderUtil::checkPath('alias.field.0_some//');
    }

    public function testGetFieldValid()
    {
        $this->assertEquals('a0_.Field0', QueryBuilderUtil::getField('a0_', 'Field0'));
    }

    /**
     * @dataProvider invalidDataProvider
     * @param string $invalid
     */
    public function testGetFieldInvalid($invalid)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertEquals('a0_.Field0', QueryBuilderUtil::getField('a0_', $invalid));
    }

    /**
     * @return array
     */
    public function invalidDataProvider(): array
    {
        return [
            ['test OR u.id < 0'],
            ['test" and '],
            ['0_some//']
        ];
    }
}
