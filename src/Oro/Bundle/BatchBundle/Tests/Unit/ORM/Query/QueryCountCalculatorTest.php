<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Query;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\BatchBundle\ORM\Query\QueryCountCalculator;
use Oro\Bundle\EntityBundle\ORM\SqlQuery;

class QueryCountCalculatorTest extends \PHPUnit_Framework_TestCase
{
    const TEST_COUNT = 42;

    /**
     * @param string $dql
     * @param string $expectedCountQuery
     * @param array  $sqlParameters
     * @param array  $types
     * @param array  $queryParameters
     *
     * @dataProvider getCountDataProvider
     */
    public function testCalculateCount(
        $dql,
        $expectedCountQuery,
        array $sqlParameters = [],
        array $types = [],
        array $queryParameters = []
    ) {
        /** @var $entityManager EntityManager|\PHPUnit_Framework_MockObject_MockObject */
        /** @var $connection Connection|\PHPUnit_Framework_MockObject_MockObject */
        /** @var $statement Statement|\PHPUnit_Framework_MockObject_MockObject */
        list($entityManager, $connection, $statement) = $this->prepareMocks();

        $query = new Query($entityManager);
        $query->setDQL($dql);
        $query->setParameters($queryParameters);

        $connection->expects($this->once())
            ->method('executeQuery')
            ->with($expectedCountQuery, $sqlParameters, $types)
            ->will($this->returnValue($statement));

        $statement->expects($this->any())
            ->method('fetch')
            ->will($this->onConsecutiveCalls(['sclr0' => self::TEST_COUNT], false));
        $statement->expects($this->any())
            ->method('fetchColumn')
            ->will($this->returnValue(self::TEST_COUNT));

        $this->assertEquals(self::TEST_COUNT, QueryCountCalculator::calculateCount($query));
    }

    /**
     * @return array
     */
    public function getCountDataProvider()
    {
        return [
            'empty'               => [
                'dql'                => 'SELECT e FROM Stub:Entity e',
                'expectedCountQuery' => 'SELECT count(@0_.a) AS sclr0 FROM  @0_',
            ],
            'empty with group by' => [
                'dql'                => 'SELECT e FROM Stub:Entity e GROUP BY e.b',
                'expectedCountQuery' => 'SELECT COUNT(*) FROM ' .
                    '(SELECT @0_.a AS a0, @0_.b AS b1 FROM  @0_ GROUP BY @0_.b) AS e',
            ],
            'single parameters'   => [
                'dql'                => 'SELECT e FROM Stub:Entity e WHERE e.a = :a AND e.b = :b',
                'expectedCountQuery' => 'SELECT count(@0_.a) AS sclr0 FROM  @0_ WHERE @0_.a = ? AND @0_.b = ?',
                'sqlParameters'      => [1, 2],
                'types'              => [Type::INTEGER, Type::INTEGER],
                'queryParameters'    => ['a' => 1, 'b' => 2],
            ],
            'multiple parameters' => [
                'dql'
                    => 'SELECT DISTINCT e.a FROM Stub:Entity e WHERE e.a = :value AND e.b = :value',
                'expectedCountQuery'
                    => 'SELECT DISTINCT count(DISTINCT @0_.a) AS sclr0 FROM  @0_ WHERE @0_.a = ? AND @0_.b = ?',
                'sqlParameters'      => [3, 3],
                'types'              => [Type::INTEGER, Type::INTEGER],
                'queryParameters'    => ['value' => 3],
            ],
        ];
    }

    /**
     * @param string $sql
     * @param bool   $useWalker
     *
     * @dataProvider getSqlCountDataProvider
     */
    public function testCalculateCountForSqlQuery($sql, $useWalker)
    {
        /** @var $entityManager EntityManager|\PHPUnit_Framework_MockObject_MockObject */
        /** @var $statement Statement|\PHPUnit_Framework_MockObject_MockObject */
        list($entityManager, , $statement) = $this->prepareMocks();

        $dbalQb = $this->getMock(
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
            ->with('(' . $sql . ')', 'e')
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

        $classMetadata = new ClassMetadata('Entity');
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
            ->setMethods(['getConfiguration', 'getClassMetadata', 'getConnection', 'getUnitOfWork'])
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

        return [$entityManager, $connection, $statement];
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected instance of Doctrine\ORM\Query or Oro\Bundle\EntityBundle\ORM\SqlQuery, "integer" given
     */
    // @codingStandardsIgnoreEnd
    public function testCalculateCountForInvalidQueryType()
    {
        QueryCountCalculator::calculateCount(123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected instance of Doctrine\ORM\Query or Oro\Bundle\EntityBundle\ORM\SqlQuery, "integer" given
     */
    // @codingStandardsIgnoreEnd
    public function testCalculateCountForInvalidQueryTypeAndUseWalker()
    {
        QueryCountCalculator::calculateCount(123, true);
    }
}
