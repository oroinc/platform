<?php

namespace Oro\Component\TestUtils\ORM;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\ChainCache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Driver\Connection;
use Oro\Component\Testing\TempDirExtension;
use Oro\Component\TestUtils\ORM\Mocks\DriverMock;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\Mocks\FetchIterator;

/**
 * The base class for ORM related test cases.
 *
 * This class is a clone of Doctrine\Tests\OrmTestCase that is excluded from doctrine package since v2.4.
 */
abstract class OrmTestCase extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var CacheProvider The metadata cache that is shared between all ORM tests */
    private $metadataCacheImpl;

    protected function getProxyDir($shouldBeCreated = true)
    {
        return $this->getTempDir('test_orm_proxies', $shouldBeCreated);
    }

    /**
     * Creates an EntityManager for testing purposes.
     *
     * NOTE: The created EntityManager will have its dependant DBAL parts completely
     * mocked out using a DriverMock, ConnectionMock, etc. These mocks can then
     * be configured in the tests to simulate the DBAL behavior that is desired
     * for a particular test,
     *
     * @param mixed        $conn
     * @param EventManager $eventManager
     * @param bool         $withSharedMetadata
     *
     * @return EntityManagerMock
     */
    protected function getTestEntityManager($conn = null, $eventManager = null, $withSharedMetadata = true)
    {
        $config = new \Doctrine\ORM\Configuration();

        $config->setMetadataCacheImpl($this->getMetadataCacheImpl($withSharedMetadata));
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver([], true));
        $config->setQueryCacheImpl($this->getQueryCacheImpl());
        $config->setProxyDir($this->getProxyDir());
        $config->setProxyNamespace('Doctrine\Tests\Proxies');

        // Namespace of custom functions is hardcoded in \Oro\ORM\Query\AST\FunctionFactory::create
        // To load the custom function we need to manually include the file
        require_once('Mocks/Cast.php');
        $config->setCustomStringFunctions(['cast' => 'Oro\ORM\Query\AST\Functions\Cast']);

        if ($conn === null) {
            $conn = [
                'driverClass'  => 'Oro\Component\TestUtils\ORM\Mocks\DriverMock',
                'wrapperClass' => 'Oro\Component\TestUtils\ORM\Mocks\ConnectionMock',
                'user'         => 'john',
                'password'     => 'wayne'
            ];
        }

        if (is_array($conn)) {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($conn, $config, $eventManager);
        }

        return EntityManagerMock::create($conn, $config, $eventManager);
    }

    /**
     * Changes a connection object for the given entity manager
     *
     * @param Connection        $connection
     * @param EntityManagerMock $em
     */
    protected function setDriverConnection(Connection $connection, EntityManagerMock $em)
    {
        /** @var DriverMock $driver */
        $driver = $em->getConnection()->getDriver();
        $driver->setDriverConnection($connection);
    }

    /**
     * Creates a mock for a statement which handles fetching the given records
     *
     * @param array $records
     * @param array $params
     * @param array $types
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createFetchStatementMock(array $records, array $params = [], array $types = [])
    {
        $statement = $this->createMock('Oro\Component\TestUtils\ORM\Mocks\StatementMock');
        $statement->expects($this->exactly(count($records) + 1))
            ->method('fetch')
            ->will(
                new \PHPUnit\Framework\MockObject\Stub\ConsecutiveCalls(
                    array_merge($records, [false])
                )
            );
        $statement->expects($this->any())
            ->method('getIterator')
            ->willReturn(new FetchIterator($statement));
        if ($params) {
            if ($types) {
                $counter = 0;
                foreach ($params as $key => $val) {
                    $statement->expects($this->at($counter++))
                        ->method('bindValue')
                        ->with($key, $val, $types[$key]);
                }
                $statement->expects($this->once())
                    ->method('execute');
            } else {
                $statement->expects($this->once())
                    ->method('execute')
                    ->with($params);
            }
        }

        return $statement;
    }

    /**
     * Creates a mock for 'Doctrine\DBAL\Driver\Connection' and sets it to the given entity manager
     *
     * @param EntityManagerMock $em
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getDriverConnectionMock(EntityManagerMock $em)
    {
        $conn = $this->createMock('\Doctrine\DBAL\Driver\Connection');
        $this->setDriverConnection($conn, $em);

        return $conn;
    }

    /**
     * Creates a mock for a statement which handles counting a number of records
     *
     * @param int $numberOfRecords
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createCountStatementMock($numberOfRecords)
    {
        $countStatement = $this->createMock('Oro\Component\TestUtils\ORM\Mocks\StatementMock');
        $countStatement->expects($this->once())->method('fetchColumn')
            ->will($this->returnValue($numberOfRecords));

        return $countStatement;
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $conn
     * @param string                                   $sql    SQL that run in database
     * @param array                                    $result data that will return after SQL execute
     * @param array                                    $params
     * @param array                                    $types
     */
    protected function setQueryExpectation(
        \PHPUnit\Framework\MockObject\MockObject $conn,
        $sql,
        $result,
        $params = [],
        $types = []
    ) {
        $stmt = $this->createFetchStatementMock($result, $params, $types);
        if ($params) {
            $conn->expects($this->once())
                ->method('prepare')
                ->with($sql)
                ->will($this->returnValue($stmt));
        } else {
            $conn
                ->expects($this->once())
                ->method('query')
                ->with($sql)
                ->will($this->returnValue($stmt));
        }
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $conn
     * @param int                                      $expectsAt
     * @param string                                   $sql
     * @param array                                    $result
     * @param array                                    $params
     * @param array                                    $types
     */
    protected function setQueryExpectationAt(
        \PHPUnit\Framework\MockObject\MockObject $conn,
        $expectsAt,
        $sql,
        $result,
        $params = [],
        $types = []
    ) {
        $stmt = $this->createFetchStatementMock($result, $params, $types);
        if ($params) {
            $conn->expects($this->at($expectsAt))
                ->method('prepare')
                ->with($sql)
                ->will($this->returnValue($stmt));
        } else {
            $conn
                ->expects($this->at($expectsAt))
                ->method('query')
                ->with($sql)
                ->will($this->returnValue($stmt));
        }
    }

    /**
     * @return CacheProvider
     */
    private function getMetadataCacheImpl($withSharedMetadata)
    {
        if (!$withSharedMetadata) {
            // do not cache anything to avoid influence between tests
            return new ChainCache();
        }

        if ($this->metadataCacheImpl === null) {
            $this->metadataCacheImpl = new ArrayCache();
        }

        return $this->metadataCacheImpl;
    }

    /**
     * @return CacheProvider
     */
    private function getQueryCacheImpl()
    {
        // do not cache anything to avoid influence between tests
        return new ChainCache();
    }
}
