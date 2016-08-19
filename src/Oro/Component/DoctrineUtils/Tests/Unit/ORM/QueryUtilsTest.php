<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Oro\Component\PhpUtils\ArrayUtil;

class QueryUtilsTest extends OrmTestCase
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

    public function testCreateResultSetMapping()
    {
        $platform = $this->getMockBuilder('Doctrine\DBAL\Platforms\AbstractPlatform')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->assertInstanceOf(
            'Oro\Component\DoctrineUtils\ORM\PlatformResultSetMapping',
            QueryUtils::createResultSetMapping($platform)
        );
    }

    /**
     * @dataProvider concatExprProvider
     *
     * @param string[] $parts
     * @param string   $expectedExpr
     */
    public function testBuildConcatExpr($parts, $expectedExpr)
    {
        $this->assertEquals($expectedExpr, QueryUtils::buildConcatExpr($parts));
    }

    public function concatExprProvider()
    {
        return [
            [[], ''],
            [[''], ''],
            [['a.field1'], 'a.field1'],
            [['a.field1', 'a.field2'], 'CONCAT(a.field1, a.field2)'],
            [['a.field1', 'a.field2', 'a.field3'], 'CONCAT(a.field1, CONCAT(a.field2, a.field3))'],
            [['a.field1', '\' \'', 'a.field3'], 'CONCAT(a.field1, CONCAT(\' \', a.field3))'],
        ];
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
        $this->assertEquals($expectedExpr, QueryUtils::getSelectExprByAlias($qb, $alias));
    }

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
            QueryUtils::getSingleRootAlias($qb)
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

        QueryUtils::getSingleRootAlias($qb);
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

        QueryUtils::getSingleRootAlias($qb);
    }

    public function testGetSingleRootAliasWhenQueryHasNoRootAliasAndNoExceptionRequested()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn([]);

        $this->assertNull(QueryUtils::getSingleRootAlias($qb, false));
    }

    /**
     * @dataProvider getPageOffsetProvider
     */
    public function testGetPageOffset($expectedOffset, $page, $limit)
    {
        $this->assertSame($expectedOffset, QueryUtils::getPageOffset($page, $limit));
    }

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

    public function testApplyEmptyJoins()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $qb->expects($this->never())
            ->method('distinct');
        $qb->expects($this->never())
            ->method('getRootAliases');

        QueryUtils::applyJoins($qb, []);
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

        QueryUtils::applyJoins($qb, $joins);
    }

    public function testNormalizeNullCriteria()
    {
        $this->assertEquals(
            new Criteria(),
            QueryUtils::normalizeCriteria(null)
        );
    }

    public function testNormalizeEmptyCriteria()
    {
        $this->assertEquals(
            new Criteria(),
            QueryUtils::normalizeCriteria([])
        );
    }

    public function testNormalizeCriteriaObject()
    {
        $criteria = new Criteria();
        $this->assertSame(
            $criteria,
            QueryUtils::normalizeCriteria($criteria)
        );
    }

    public function testNormalizeCriteriaArray()
    {
        $expectedCriteria = new Criteria();
        $expectedCriteria->andWhere(Criteria::expr()->eq('field', 'value'));

        $this->assertEquals(
            $expectedCriteria,
            QueryUtils::normalizeCriteria(['field' => 'value'])
        );
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

        QueryUtils::removeUnusedParameters($qb);
    }

    /**
     * @dataProvider dqlParametersDataProvider
     *
     * @param string $dql
     * @param string $parameter
     * @param bool $expected
     */
    public function testDqlContainsParameter($dql, $parameter, $expected)
    {
        $this->assertEquals($expected, QueryUtils::dqlContainsParameter($dql, $parameter));
    }

    public function dqlParametersDataProvider()
    {
        $dql = 'SELECT a.name FROM Some:Other as a WHERE a.name = :param1
            AND a.name != :param2 AND a.status = ?1';

        return [
            [$dql, 'param1', true],
            [$dql, 'param5', false],
            [$dql, 'param11', false],
            [$dql, 0, false],
            [$dql, 1, true],
        ];
    }

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
     * @dataProvider getJoinClassDataProvider
     */
    public function testGetJoinClass(callable $qbFactory, $joinPath, $expectedClass)
    {
        $qb = $qbFactory($this->em);

        $this->assertEquals(
            $expectedClass,
            QueryUtils::getJoinClass($qb, ArrayUtil::getIn($qb->getDqlPart('join'), $joinPath))
        );
    }

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

        $this->assertNull(QueryUtils::findJoinByAlias($qb, 'p'));
        $this->assertEquals('g', QueryUtils::findJoinByAlias($qb, 'g')->getAlias());
        $this->assertEquals('i', QueryUtils::findJoinByAlias($qb, 'i')->getAlias());
        $this->assertNull(QueryUtils::findJoinByAlias($qb, 'w'));
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return new QueryBuilder($this->getMock('Doctrine\ORM\EntityManagerInterface'));
    }
}
