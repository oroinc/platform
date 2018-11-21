<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\BatchBundle\ORM\Query\QueryCountCalculator;
use Oro\Bundle\EntityBundle\ORM\SqlQuery;

class QueryCountCalculatorTest extends \PHPUnit\Framework\TestCase
{
    const TEST_COUNT = 42;

    /**
     * @param string $dql
     * @param string $expectedSql
     * @param array  $sqlParams
     * @param array  $types
     * @param array  $queryParams
     * @param array  $useWalker
     *
     * @dataProvider getCountDataProvider
     */
    public function testCalculateCount(
        $dql,
        $expectedSql,
        array $sqlParams = [],
        array $types = [],
        array $queryParams = [],
        $useWalker = null
    ) {
        /** @var $entityManager EntityManager|\PHPUnit\Framework\MockObject\MockObject */
        /** @var $connection Connection|\PHPUnit\Framework\MockObject\MockObject */
        /** @var $statement Statement|\PHPUnit\Framework\MockObject\MockObject */
        list($entityManager, $connection, $statement) = $this->prepareMocks();

        $query = new Query($entityManager);
        $query->setDQL($dql);
        $query->setParameters($queryParams);

        $connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedSql, $sqlParams, $types)
            ->will($this->returnValue($statement));

        $statement->expects($this->any())
            ->method('fetch')
            ->will($this->onConsecutiveCalls(['sclr_0' => self::TEST_COUNT], false));
        $statement->expects($this->any())
            ->method('fetchColumn')
            ->will($this->returnValue(self::TEST_COUNT));

        $this->assertEquals(self::TEST_COUNT, QueryCountCalculator::calculateCount($query, $useWalker));
    }

    /**
     * @return array
     */
    public function getCountDataProvider()
    {
        return [
            'empty'                                  => [
                'dql'         => 'SELECT e FROM Stub:Entity e',
                'expectedSql' => 'SELECT count(t0_.a) AS sclr_0 FROM  t0_',
            ],
            'empty with group by'                    => [
                'dql'         => 'SELECT e FROM Stub:Entity e GROUP BY e.b',
                'expectedSql' => 'SELECT COUNT(*)'
                    . ' FROM (SELECT t0_.a AS a_0, t0_.b AS b_1 FROM  t0_ GROUP BY t0_.b)'
                    . ' AS count_query',
            ],
            'single parameters'                      => [
                'dql'         => 'SELECT e FROM Stub:Entity e WHERE e.a = :a AND e.b = :b',
                'expectedSql' => 'SELECT count(t0_.a) AS sclr_0 FROM  t0_ WHERE t0_.a = ? AND t0_.b = ?',
                'sqlParams'   => [1, 2],
                'types'       => [Type::INTEGER, Type::INTEGER],
                'queryParams' => ['a' => 1, 'b' => 2],
            ],
            'single parameters (disable walker)'     => [
                'dql'         => 'SELECT e FROM Stub:Entity e WHERE e.a = :a AND e.b = :b',
                'expectedSql' => 'SELECT COUNT(*)'
                    . ' FROM (SELECT t0_.a AS a_0, t0_.b AS b_1 FROM  t0_ WHERE t0_.a = ? AND t0_.b = ?)'
                    . ' AS count_query',
                'sqlParams'   => [1, 2],
                'types'       => [Type::INTEGER, Type::INTEGER],
                'queryParams' => ['a' => 1, 'b' => 2],
                'useWalker'   => false
            ],
            'multiple parameters'                    => [
                'dql'         => 'SELECT DISTINCT e.a FROM Stub:Entity e WHERE e.a = :value AND e.b = :value',
                'expectedSql' => 'SELECT DISTINCT count(DISTINCT t0_.a) AS sclr_0'
                    . ' FROM  t0_ WHERE t0_.a = ? AND t0_.b = ?',
                'sqlParams'   => [3, 3],
                'types'       => [Type::INTEGER, Type::INTEGER],
                'queryParams' => ['value' => 3],
            ],
            'multiple parameters (disable walker)'   => [
                'dql'         => 'SELECT DISTINCT e.a FROM Stub:Entity e WHERE e.a = :value AND e.b = :value',
                'expectedSql' => 'SELECT COUNT(*)'
                    . ' FROM (SELECT DISTINCT t0_.a AS a_0 FROM  t0_ WHERE t0_.a = ? AND t0_.b = ?)'
                    . ' AS count_query',
                'sqlParams'   => [3, 3],
                'types'       => [Type::INTEGER, Type::INTEGER],
                'queryParams' => ['value' => 3],
                'useWalker'   => false
            ],
            'positional parameters'                  => [
                'dql'         => 'SELECT e.a FROM Stub:Entity e WHERE e.a = ?1 AND e.b = ?0',
                'expectedSql' => 'SELECT count(t0_.a) AS sclr_0 FROM  t0_ WHERE t0_.a = ? AND t0_.b = ?',
                'sqlParams'   => [4, 3],
                'types'       => [Type::INTEGER, Type::INTEGER],
                'queryParams' => [3, 4],
            ],
            'positional parameters (disable walker)' => [
                'dql'         => 'SELECT e.a FROM Stub:Entity e WHERE e.a = ?1 AND e.b = ?0',
                'expectedSql' => 'SELECT COUNT(*)'
                    . ' FROM (SELECT t0_.a AS a_0 FROM  t0_ WHERE t0_.a = ? AND t0_.b = ?)'
                    . ' AS count_query',
                'sqlParams'   => [4, 3],
                'types'       => [Type::INTEGER, Type::INTEGER],
                'queryParams' => [3, 4],
                'useWalker'   => false
            ],
        ];
    }

    /**
     * @param string $sql
     * @param bool   $useWalker
     *
     * @dataProvider getSqlCountDataProvider
     */
    public function testCalculateCountForSqlQuery($sql, $useWalker = null)
    {
        /** @var $entityManager EntityManager|\PHPUnit\Framework\MockObject\MockObject */
        /** @var $statement Statement|\PHPUnit\Framework\MockObject\MockObject */
        list($entityManager, , $statement) = $this->prepareMocks();

        $dbalQb = $this->createMock(
            'Doctrine\DBAL\Query\QueryBuilder',
            ['getSQL', 'resetQueryParts', 'select', 'from', 'execute'],
            [],
            '',
            false
        );

        $query = new SqlQuery($entityManager);
        $query->setQueryBuilder($dbalQb);

        $dbalQb->expects($this->once())
            ->method('getSQL')
            ->will($this->returnValue($sql));
        $dbalQb->expects($this->once())
            ->method('resetQueryParts')
            ->will($this->returnSelf());
        $dbalQb->expects($this->once())
            ->method('select')
            ->with('COUNT(*)')
            ->will($this->returnSelf());
        $dbalQb->expects($this->once())
            ->method('from')
            ->with('(' . $sql . ')', 'count_query')
            ->will($this->returnSelf());
        $dbalQb->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($statement));

        $statement->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(self::TEST_COUNT));

        $this->assertEquals(self::TEST_COUNT, QueryCountCalculator::calculateCount($query, $useWalker));
    }

    public function getSqlCountDataProvider()
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

    /**
     * @return array
     */
    protected function prepareMocks()
    {
        $configuration = new Configuration();

        $configuration->addEntityNamespace('Stub', 'Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub');

        $classMetadata = new ClassMetadata(\stdClass::class);
        $classMetadata->mapField(['fieldName' => 'a', 'columnName' => 'a']);
        $classMetadata->mapField(['fieldName' => 'b', 'columnName' => 'b']);
        $classMetadata->setIdentifier(['a']);

        $platform = $this->getMockBuilder('Doctrine\DBAL\Platforms\AbstractPlatform')
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $statement = $this->getMockBuilder('Doctrine\DBAL\Statement')
            ->setMethods(['fetch', 'fetchColumn', 'closeCursor'])
            ->disableOriginalConstructor()
            ->getMock();

        $driverConnection = $this->getMockBuilder('Doctrine\DBAL\Driver\Connection')
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $driverConnection->expects($this->any())
            ->method('query')
            ->will($this->returnValue($statement));

        $driver = $this->getMockBuilder('Doctrine\DBAL\Driver')
            ->setMethods(['connect', 'getDatabasePlatform'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $driver->expects($this->any())
            ->method('connect')
            ->will($this->returnValue($driverConnection));
        $driver->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($platform));

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->setMethods(['getDatabasePlatform', 'executeQuery'])
            ->setConstructorArgs([[], $driver])
            ->getMock();
        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($platform));

        /** @var UnitOfWork $unitOfWork */
        $unitOfWork = $this->getMockBuilder('UnitOfWork')
            ->setMethods(['getEntityPersister'])
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->setMethods(['getConfiguration', 'getClassMetadata', 'getConnection', 'getUnitOfWork', 'getEventManager'])
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($classMetadata));
        $entityManager->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($connection));
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));

        $eventManager = $this->createMock(EventManager::class);
        $eventManager->expects($this->any())
            ->method('addEventListener');

        $entityManager->expects($this->any())
            ->method('getEventManager')
            ->will($this->returnValue($eventManager));

        return [$entityManager, $connection, $statement];
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected instance of Doctrine\ORM\Query, Oro\Component\DoctrineUtils\ORM\SqlQuery or Doctrine\DBAL\Query\QueryBuilder, "integer" given
     */
    // @codingStandardsIgnoreEnd
    public function testCalculateCountForInvalidQueryType()
    {
        QueryCountCalculator::calculateCount(123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected instance of Doctrine\ORM\Query, Oro\Component\DoctrineUtils\ORM\SqlQuery or Doctrine\DBAL\Query\QueryBuilder, "integer" given
     */
    // @codingStandardsIgnoreEnd
    public function testCalculateCountForInvalidQueryTypeAndUseWalker()
    {
        QueryCountCalculator::calculateCount(123, true);
    }
}
