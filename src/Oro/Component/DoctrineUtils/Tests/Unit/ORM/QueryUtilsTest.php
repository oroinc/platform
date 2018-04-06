<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryUtils;
use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Component\TestUtils\ORM\OrmTestCase;

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

    public function testCloneQuery()
    {
        $query = new Query($this->em);
        $query->setDQL('SELECT e FROM Test:Item e WHERE e.id = :id');
        $query->setHint('hint1', 'value1');
        $query->setParameter('id', 123);

        $clonedQuery = QueryUtils::cloneQuery($query);
        self::assertNotSame($query, $clonedQuery);
        self::assertEquals($query, $clonedQuery);
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

    public function testAddTreeWalkerWhenQueryDoesNotHaveHints()
    {
        $query = new Query($this->em);

        self::assertTrue(
            QueryUtils::addTreeWalker($query, 'Test\Walker')
        );
        self::assertEquals(
            [
                Query::HINT_CUSTOM_TREE_WALKERS => ['Test\Walker']
            ],
            $query->getHints()
        );
    }

    public function testAddTreeWalkerWhenQueryHasOtherHints()
    {
        $query = new Query($this->em);
        $query->setHint('test', 'value');

        self::assertTrue(
            QueryUtils::addTreeWalker($query, 'Test\Walker')
        );
        self::assertEquals(
            [
                'test'                          => 'value',
                Query::HINT_CUSTOM_TREE_WALKERS => ['Test\Walker']
            ],
            $query->getHints()
        );
    }

    public function testAddTreeWalkerWhenQueryHasOtherTreeWalkers()
    {
        $query = new Query($this->em);
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, ['Test\OtherWalker']);

        self::assertTrue(
            QueryUtils::addTreeWalker($query, 'Test\Walker')
        );
        self::assertEquals(
            [
                Query::HINT_CUSTOM_TREE_WALKERS => ['Test\OtherWalker', 'Test\Walker']
            ],
            $query->getHints()
        );
    }

    public function testAddTreeWalkerWhenQueryAlreadyHasTreeWalker()
    {
        $query = new Query($this->em);
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, ['Test\Walker']);

        self::assertFalse(
            QueryUtils::addTreeWalker($query, 'Test\Walker')
        );
        self::assertEquals(
            [
                Query::HINT_CUSTOM_TREE_WALKERS => ['Test\Walker']
            ],
            $query->getHints()
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
     * @dataProvider getDqlAliasesDataProvider
     */
    public function testGetDqlAliases(callable $dqlFactory, array $expectedAliases)
    {
        $this->assertEquals($expectedAliases, QueryUtils::getDqlAliases($dqlFactory($this->em)));
    }

    public function getDqlAliasesDataProvider()
    {
        return [
            'query with fully qualified entity name' => [
                function (EntityManager $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Person', 'p')
                        ->join('p.bestItem', 'i')
                        ->getDQL();
                },
                ['p', 'i'],
            ],
            'query aliased entity name' => [
                function (EntityManager $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from('Test:Person', 'p')
                        ->join('p.bestItem', 'i')
                        ->getDQL();
                },
                ['p', 'i'],
            ],
            'query with subquery' => [
                function (EntityManager $em) {
                    $qb = $em->createQueryBuilder();

                    return $qb
                        ->select('p')
                        ->from('Test:Person', 'p')
                        ->join('p.bestItem', 'i')
                        ->where(
                            $qb->expr()->exists(
                                $em->createQueryBuilder()
                                    ->select('p2')
                                    ->from('Test:Person', 'p2')
                                    ->join('p2.groups', '_g2')
                                    ->where('p2.id = p.id')
                            )
                        )
                        ->getDQL();
                },
                ['p', 'i', 'p2', '_g2'],
            ],
            'query with newlines after aliases, AS keyword and case insensitive' => [
                function () {
                    return <<<DQL
SELECT  p
FROM  TestPerson  p
JOIN  p.bestItem  AS  i
WHERE EXISTS(
    SELECT p2
    FROM TestPerson  p2
    join  p2.groups  _g2
    WHERE  p2.id  =  p.id
)
DQL
                    ;
                },
                ['p', 'i', 'p2', '_g2'],
            ],
        ];
    }

    /**
     * @dataProvider replaceDqlAliasesProvider
     */
    public function testReplaceDqlAliases($dql, array $replacements, $expectedDql)
    {
        $this->assertEquals($expectedDql, QueryUtils::replaceDqlAliases($dql, $replacements));
    }

    public function replaceDqlAliasesProvider()
    {
        return [
            [
                <<<DQL
SELECT eu.id
FROM OroEmailBundle:EmailUser eu
LEFT JOIN eu.email e
LEFT JOIN eu.mailboxOwner mb
LEFT JOIN e.recipients r_to
LEFT JOIN eu.folders f
LEFT JOIN f.origin o
LEFT JOIN e.emailBody eb
WHERE (EXISTS(
    SELECT 1
    FROM OroEmailBundle:EmailOrigin _eo
    JOIN _eo.folders _f
    JOIN _f.emailUsers _eu
    WHERE _eo.isActive = true AND _eu.id = eu.id
))
AND e.head = true AND (eu.owner = :owner AND eu.organization  = :organization) AND e.subject LIKE :subject1027487935
DQL
                ,
                [
                    ['eu', 'eur'],
                    ['e', 'er'],
                    ['mb', 'mbr'],
                    ['r_to', 'r_tor'],
                    ['f', 'fr'],
                    ['o', 'or'],
                    ['eb', 'ebr'],
                    ['_eo', '_eor'],
                    ['_f', '_fr'],
                    ['_eu', '_eur'],
                ],
                <<<DQL
SELECT eur.id
FROM OroEmailBundle:EmailUser eur
LEFT JOIN eur.email er
LEFT JOIN eur.mailboxOwner mbr
LEFT JOIN er.recipients r_tor
LEFT JOIN eur.folders fr
LEFT JOIN fr.origin or
LEFT JOIN er.emailBody ebr
WHERE (EXISTS(
    SELECT 1
    FROM OroEmailBundle:EmailOrigin _eor
    JOIN _eor.folders _fr
    JOIN _fr.emailUsers _eur
    WHERE _eor.isActive = true AND _eur.id = eur.id
))
AND er.head = true AND (eur.owner = :owner AND eur.organization  = :organization) AND er.subject LIKE :subject1027487935
DQL
            ],
        ];
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

    public function testIsToOne()
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Person', 'p')
            ->join('p.bestItem', 'i')
            ->join('i.owner', 'o')
            ->join('i.persons', 'persons')
            ->join('persons.bestItem', 'bi');

        $this->assertTrue(QueryUtils::isToOne($qb, 'i'));
        $this->assertTrue(QueryUtils::isToOne($qb, 'o'));
        $this->assertFalse(QueryUtils::isToOne($qb, 'bi'));
        $this->assertFalse(QueryUtils::isToOne($qb, 'persons'));
        $this->assertFalse(QueryUtils::isToOne($qb, 'nonExistingAlias'));
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return new QueryBuilder($this->createMock('Doctrine\ORM\EntityManagerInterface'));
    }
}
