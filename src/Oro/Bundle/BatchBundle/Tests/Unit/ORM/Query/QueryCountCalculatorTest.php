<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Oro\Bundle\BatchBundle\ORM\Query\QueryCountCalculator;
use Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub\Entity;
use Oro\Component\DoctrineUtils\ORM\SqlQuery;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;
use Oro\Component\Testing\Unit\ORM\Mocks\DriverMock;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QueryCountCalculatorTest extends OrmTestCase
{
    private const TEST_COUNT = 42;

    /** @var EntityManagerInterface */
    private $em;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var Statement|\PHPUnit\Framework\MockObject\MockObject */
    private $statement;

    protected function setUp(): void
    {
        $this->statement = $this->createMock(Statement::class);

        $driverConnection = $this->createMock(DriverConnection::class);
        $driverConnection->expects($this->any())
            ->method('query')
            ->willReturn($this->statement);

        $this->connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['executeQuery'])
            ->setConstructorArgs([[], new DriverMock()])
            ->getMock();

        $this->em = $this->getTestEntityManager($this->connection);
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
    }

    private function getQuery(string $dql, array $params = null): Query
    {
        $query = new Query($this->em);
        $query->setDQL($dql);
        if (null !== $params) {
            $query->setParameters($params);
        }

        return $query;
    }

    public function testCalculateCountForQueryWithoutParameters()
    {
        $query = $this->getQuery('SELECT e FROM ' . Entity::class . ' e');
        $expectedSql = 'SELECT count(e0_.a) AS sclr_0 FROM Entity e0_';

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedSql, [], [])
            ->willReturn($this->statement);
        $this->statement->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(['sclr_0' => self::TEST_COUNT], false);

        $this->assertEquals(self::TEST_COUNT, QueryCountCalculator::calculateCount($query));
    }

    public function testCalculateCountForQueryWithGroupBy()
    {
        $query = $this->getQuery('SELECT e FROM ' . Entity::class . ' e GROUP BY e.b');
        $expectedSql = 'SELECT COUNT(*)'
            . ' FROM (SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ GROUP BY e0_.b)'
            . ' AS count_query';

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedSql, [], [])
            ->willReturn($this->statement);
        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(self::TEST_COUNT);

        $this->assertEquals(self::TEST_COUNT, QueryCountCalculator::calculateCount($query));
    }

    public function testCalculateCountForQueryWithParameters()
    {
        $query = $this->getQuery(
            'SELECT e FROM ' . Entity::class . ' e WHERE e.a = :a AND e.b = :b',
            ['a' => 1, 'b' => 2]
        );
        $expectedSql = 'SELECT count(e0_.a) AS sclr_0 FROM Entity e0_ WHERE e0_.a = ? AND e0_.b = ?';
        $sqlParams = [1, 2];
        $types = [Types::INTEGER, Types::INTEGER];

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedSql, $sqlParams, $types)
            ->willReturn($this->statement);
        $this->statement->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(['sclr_0' => self::TEST_COUNT], false);

        $this->assertEquals(self::TEST_COUNT, QueryCountCalculator::calculateCount($query));
    }

    public function testCalculateCountForQueryWithParametersAndDisabledCountWalker()
    {
        $query = $this->getQuery(
            'SELECT e FROM ' . Entity::class . ' e WHERE e.a = :a AND e.b = :b',
            ['a' => 1, 'b' => 2]
        );
        $expectedSql = 'SELECT COUNT(*)'
            . ' FROM (SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ WHERE e0_.a = ? AND e0_.b = ?)'
            . ' AS count_query';
        $sqlParams = [1, 2];
        $types = [Types::INTEGER, Types::INTEGER];

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedSql, $sqlParams, $types)
            ->willReturn($this->statement);
        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(self::TEST_COUNT);

        $this->assertEquals(self::TEST_COUNT, QueryCountCalculator::calculateCount($query, false));
    }

    public function testCalculateCountForQueryWithParameterUsedSeveralTimes()
    {
        $query = $this->getQuery(
            'SELECT DISTINCT e.a FROM ' . Entity::class . ' e WHERE e.a = :value AND e.b = :value',
            ['value' => 3]
        );
        $expectedSql = 'SELECT DISTINCT count(DISTINCT e0_.a) AS sclr_0'
            . ' FROM Entity e0_ WHERE e0_.a = ? AND e0_.b = ?';
        $sqlParams = [3, 3];
        $types = [Types::INTEGER, Types::INTEGER];

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedSql, $sqlParams, $types)
            ->willReturn($this->statement);
        $this->statement->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(['sclr_0' => self::TEST_COUNT], false);

        $this->assertEquals(self::TEST_COUNT, QueryCountCalculator::calculateCount($query));
    }

    public function testCalculateCountForQueryWithParameterUsedSeveralTimesAndDisabledCountWalker()
    {
        $query = $this->getQuery(
            'SELECT DISTINCT e.a FROM ' . Entity::class . ' e WHERE e.a = :value AND e.b = :value',
            ['value' => 3]
        );
        $expectedSql = 'SELECT COUNT(*)'
            . ' FROM (SELECT DISTINCT e0_.a AS a_0 FROM Entity e0_ WHERE e0_.a = ? AND e0_.b = ?)'
            . ' AS count_query';
        $sqlParams = [3, 3];
        $types = [Types::INTEGER, Types::INTEGER];

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedSql, $sqlParams, $types)
            ->willReturn($this->statement);
        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(self::TEST_COUNT);

        $this->assertEquals(self::TEST_COUNT, QueryCountCalculator::calculateCount($query, false));
    }

    public function testCalculateCountForQueryWithPositionalParameters()
    {
        $query = $this->getQuery(
            'SELECT e.a FROM ' . Entity::class . ' e WHERE e.a = ?1 AND e.b = ?0',
            [3, 4]
        );
        $expectedSql = 'SELECT count(e0_.a) AS sclr_0 FROM Entity e0_ WHERE e0_.a = ? AND e0_.b = ?';
        $sqlParams = [4, 3];
        $types = [Types::INTEGER, Types::INTEGER];

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedSql, $sqlParams, $types)
            ->willReturn($this->statement);
        $this->statement->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(['sclr_0' => self::TEST_COUNT], false);

        $this->assertEquals(self::TEST_COUNT, QueryCountCalculator::calculateCount($query));
    }

    public function testCalculateCountForQueryWithPositionalParametersAndDisabledCountWalker()
    {
        $query = $this->getQuery(
            'SELECT e.a FROM ' . Entity::class . ' e WHERE e.a = ?1 AND e.b = ?0',
            [3, 4]
        );
        $expectedSql = 'SELECT COUNT(*)'
            . ' FROM (SELECT e0_.a AS a_0 FROM Entity e0_ WHERE e0_.a = ? AND e0_.b = ?)'
            . ' AS count_query';
        $sqlParams = [4, 3];
        $types = [Types::INTEGER, Types::INTEGER];

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedSql, $sqlParams, $types)
            ->willReturn($this->statement);
        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(self::TEST_COUNT);

        $this->assertEquals(self::TEST_COUNT, QueryCountCalculator::calculateCount($query, false));
    }

    public function testCalculateCountDistinct()
    {
        $query = $this->getQuery(
            'SELECT e FROM ' . Entity::class . ' e WHERE e.a = :a AND e.b = :b',
            ['a' => 1, 'b' => 2]
        );
        $expectedSql = 'SELECT count(DISTINCT e0_.a) AS sclr_0 FROM Entity e0_ WHERE e0_.a = ? AND e0_.b = ?';
        $sqlParams = [1, 2];
        $types = [Types::INTEGER, Types::INTEGER];

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedSql, $sqlParams, $types)
            ->willReturn($this->statement);
        $this->statement->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(['sclr_0' => self::TEST_COUNT], false);

        $this->assertEquals(self::TEST_COUNT, QueryCountCalculator::calculateCountDistinct($query));
    }

    public function testCalculateCountDistinctWhenCountWalkerIsNotUsed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The usage of DISTINCT keyword can be forced only together with Doctrine\ORM\Tools\Pagination\CountWalker.'
        );

        $query = $this->getQuery('SELECT e FROM ' . Entity::class . ' e GROUP BY e.b');
        QueryCountCalculator::calculateCountDistinct($query);
    }

    public function testCalculateCountWhenResultSetMappingIsAlreadyBuilt()
    {
        $query = $this->getQuery(
            'SELECT e FROM ' . Entity::class . ' e WHERE e.a = :a',
            ['a' => 1]
        );
        $expectedSql = 'SELECT count(e0_.a) AS sclr_0 FROM Entity e0_ WHERE e0_.a = ?';
        $sqlParams = [1];
        $types = [Types::INTEGER];

        $expectedSourceSql = 'SELECT e0_.a AS a_0, e0_.b AS b_1 FROM Entity e0_ WHERE e0_.a = ?';
        $expectedEntity1 = new Entity();
        $expectedEntity1->a = 'a1';
        $expectedEntity1->b = 'b1';
        $expectedEntity2 = new Entity();
        $expectedEntity2->a = 'a2';
        $expectedEntity2->b = 'b2';

        $this->connection->expects($this->exactly(3))
            ->method('executeQuery')
            ->withConsecutive(
                [$expectedSourceSql, $sqlParams, $types],
                [$expectedSql, $sqlParams, $types],
                [$expectedSourceSql, $sqlParams, $types]
            )
            ->willReturn($this->statement);
        $this->statement->expects($this->exactly(6))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['a_0' => $expectedEntity1->a, 'b_1' => $expectedEntity1->b],
                false,
                ['sclr_0' => self::TEST_COUNT],
                false,
                ['a_0' => $expectedEntity2->a, 'b_1' => $expectedEntity2->b],
                false
            );

        // execute the source query to initialize its ResultSetMapping
        $this->assertEquals([$expectedEntity1], $query->execute());

        $this->assertEquals(self::TEST_COUNT, QueryCountCalculator::calculateCount($query));

        // execute the source query again to make sure that its ResultSetMapping is valid
        $this->assertEquals([$expectedEntity2], $query->execute());
    }

    /**
     * @dataProvider getSqlCountDataProvider
     */
    public function testCalculateCountForSqlQuery(string $sql, bool $useWalker = null)
    {
        $qb = $this->createMock(SqlQueryBuilder::class);

        $query = new SqlQuery($this->em);
        $query->setSqlQueryBuilder($qb);

        $qb->expects($this->once())
            ->method('getSQL')
            ->willReturn($sql);
        $qb->expects($this->once())
            ->method('resetQueryParts')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(*)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('from')
            ->with('(' . $sql . ')', 'count_query')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(self::TEST_COUNT);

        $this->assertEquals(self::TEST_COUNT, QueryCountCalculator::calculateCount($query, $useWalker));
    }

    public function getSqlCountDataProvider(): array
    {
        return [
            [
                'sql' => 'SELECT id FROM test'
            ],
            [
                'sql'       => 'SELECT id FROM test',
                'useWalker' => false
            ],
            [
                'sql'       => 'SELECT id FROM test',
                'useWalker' => true
            ]
        ];
    }

    public function testCalculateCountForInvalidQueryType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected instance of Doctrine\ORM\Query, Oro\Component\DoctrineUtils\ORM\SqlQuery'
            . ' or Doctrine\DBAL\Query\QueryBuilder, "integer" given.'
        );
        QueryCountCalculator::calculateCount(123);
    }

    public function testCalculateCountForInvalidQueryTypeAndUseWalker()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected instance of Doctrine\ORM\Query, Oro\Component\DoctrineUtils\ORM\SqlQuery'
            . ' or Doctrine\DBAL\Query\QueryBuilder, "integer" given.'
        );
        QueryCountCalculator::calculateCount(123, true);
    }
}
