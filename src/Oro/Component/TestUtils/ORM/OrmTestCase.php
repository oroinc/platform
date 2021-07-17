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
use Oro\Component\TestUtils\ORM\Mocks\StatementMock;

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

    /** @var array|null */
    private $queryExpectations;

    /**
     * @after
     */
    protected function resetQueryExpectations()
    {
        $this->queryExpectations = null;
    }

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

        // The namespace of custom functions is hardcoded in \Oro\ORM\Query\AST\FunctionFactory::create, so we are
        // making our mock of 'CAST' available in Oro\ORM\Query\AST\Platform\Functions\Mock\ namespace:
        if (!\class_exists('Oro\ORM\Query\AST\Platform\Functions\Mock\Cast', false)) {
            \class_alias(
                \Oro\Component\TestUtils\ORM\Mocks\Cast::class,
                'Oro\ORM\Query\AST\Platform\Functions\Mock\Cast'
            );
        }
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
        $statement = $this->createMock(StatementMock::class);
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
                $withConsecutive = [];
                foreach ($params as $key => $val) {
                    $withConsecutive[] = [$key, $val, $types[$key]];
                }
                $statement->expects($this->exactly(count($params)))
                    ->method('bindValue')
                    ->withConsecutive(...$withConsecutive);
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
     * @return Connection|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getDriverConnectionMock(EntityManagerMock $em)
    {
        $conn = $this->createMock(Connection::class);
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
        $countStatement = $this->createMock(StatementMock::class);
        $countStatement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($numberOfRecords);

        return $countStatement;
    }

    /**
     * @param Connection|\PHPUnit\Framework\MockObject\MockObject $conn
     * @param string|\PHPUnit\Framework\Constraint\Constraint     $sql
     * @param array                                               $result
     * @param array                                               $params
     * @param array                                               $types
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
                ->willReturn($stmt);
        } else {
            $conn->expects($this->once())
                ->method('query')
                ->with($sql)
                ->willReturn($stmt);
        }
    }

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
     * @param string|\PHPUnit\Framework\Constraint\Constraint $sql
     * @param array                                           $result
     * @param array                                           $params
     * @param array                                           $types
     */
    protected function addQueryExpectation(
        $sql,
        $result,
        $params = [],
        $types = []
    ) {
        $stmt = $this->createFetchStatementMock($result, $params, $types);
        if ($params) {
            $this->queryExpectations['prepare'][] = [$sql, $stmt];
        } else {
            $this->queryExpectations['query'][] = [$sql, $stmt];
        }
    }

    protected function applyQueryExpectations(\PHPUnit\Framework\MockObject\MockObject $conn)
    {
        if (!$this->queryExpectations) {
            throw new \LogicException('The addQueryExpectation() should be called before.');
        }

        $queryExpectations = $this->queryExpectations;
        $this->queryExpectations = null;

        foreach ($queryExpectations as $method => $queries) {
            $with = [];
            $will = [];
            foreach ($queries as [$sql, $stmt]) {
                $with[] = [$sql];
                $will[] = $stmt;
            }
            $conn->expects($this->exactly(count($queries)))
                ->method($method)
                ->withConsecutive(...$with)
                ->willReturnOnConsecutiveCalls(...$will);
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
    protected function getQueryCacheImpl()
    {
        // do not cache anything to avoid influence between tests
        return new ChainCache();
    }
}
