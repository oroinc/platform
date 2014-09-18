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
        array $sqlParameters = array(),
        array $types = array(),
        array $queryParameters = array()
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
        return array(
            'empty'               => array(
                'dql'                => 'SELECT e FROM Stub:Entity e',
                'expectedCountQuery' => 'SELECT count(@0_.a) AS sclr0 FROM  @0_',
            ),
            'empty with group by' => array(
                'dql'                => 'SELECT e FROM Stub:Entity e GROUP BY e.b',
                'expectedCountQuery' => 'SELECT COUNT(*) FROM ' .
                    '(SELECT @0_.a AS a0, @0_.b AS b1 FROM  @0_ GROUP BY @0_.b) AS e',
            ),
            'single parameters'   => array(
                'dql'                => 'SELECT e FROM Stub:Entity e WHERE e.a = :a AND e.b = :b',
                'expectedCountQuery' => 'SELECT count(@0_.a) AS sclr0 FROM  @0_ WHERE @0_.a = ? AND @0_.b = ?',
                'sqlParameters'      => array(1, 2),
                'types'              => array(Type::INTEGER, Type::INTEGER),
                'queryParameters'    => array('a' => 1, 'b' => 2),
            ),
            'multiple parameters' => array(
                'dql'
                    => 'SELECT DISTINCT e.a FROM Stub:Entity e WHERE e.a = :value AND e.b = :value',
                'expectedCountQuery'
                    => 'SELECT DISTINCT count(DISTINCT @0_.a) AS sclr0 FROM  @0_ WHERE @0_.a = ? AND @0_.b = ?',
                'sqlParameters'      => array(3, 3),
                'types'              => array(Type::INTEGER, Type::INTEGER),
                'queryParameters'    => array('value' => 3),
            ),
        );
    }

    /**
     * @return array
     */
    protected function prepareMocks()
    {
        $configuration = new Configuration();

        $configuration->addEntityNamespace('Stub', 'Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub');

        $classMetadata = new ClassMetadata('Entity');
        $classMetadata->mapField(array('fieldName' => 'a', 'columnName' => 'a'));
        $classMetadata->mapField(array('fieldName' => 'b', 'columnName' => 'b'));
        $classMetadata->setIdentifier(array('a'));

        $platform = $this->getMockBuilder('Doctrine\DBAL\Platforms\AbstractPlatform')
            ->setMethods(array())
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $statement = $this->getMockBuilder('Doctrine\DBAL\Statement')
            ->setMethods(array('fetch', 'fetchColumn', 'closeCursor'))
            ->disableOriginalConstructor()
            ->getMock();

        $driverConnection = $this->getMockBuilder('Doctrine\DBAL\Driver\Connection')
            ->setMethods(array('query'))
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $driverConnection->expects($this->any())
            ->method('query')
            ->will($this->returnValue($statement));

        $driver = $this->getMockBuilder('Doctrine\DBAL\Driver')
            ->setMethods(array('connect', 'getDatabasePlatform'))
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $driver->expects($this->any())
            ->method('connect')
            ->will($this->returnValue($driverConnection));
        $driver->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($platform));

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->setMethods(array('getDatabasePlatform', 'executeQuery'))
            ->setConstructorArgs(array(array(), $driver))
            ->getMock();
        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($platform));

        /** @var UnitOfWork $unitOfWork */
        $unitOfWork = $this->getMockBuilder('UnitOfWork')
            ->setMethods(array('getEntityPersister'))
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->setMethods(array('getConfiguration', 'getClassMetadata', 'getConnection', 'getUnitOfWork'))
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

        return array($entityManager, $connection, $statement);
    }
}
