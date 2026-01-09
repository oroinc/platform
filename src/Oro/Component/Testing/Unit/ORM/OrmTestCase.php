<?php

namespace Oro\Component\Testing\Unit\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Oro\Component\Testing\TempDirExtension;
use Oro\Component\Testing\Unit\ORM\Mocks\ConnectionMock;
use Oro\Component\Testing\Unit\ORM\Mocks\DriverMock;
use Oro\Component\Testing\Unit\ORM\Mocks\EntityManagerMock;
use Oro\Component\Testing\Unit\ORM\Mocks\ResultMock;
use Oro\Component\Testing\Unit\ORM\Mocks\StatementMock;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\ConsecutiveCalls;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * The base class for ORM related test cases.
 */
abstract class OrmTestCase extends TestCase
{
    use TempDirExtension;

    private ?CacheItemPoolInterface $metadataCacheImpl = null;
    private array $queryExpectations = [];

    /**
     * @after
     */
    protected function resetQueryExpectations(): void
    {
        $this->queryExpectations = [];
    }

    protected function getProxyDir(bool $shouldBeCreated = true): string
    {
        return $this->getTempDir('test_orm_proxies', $shouldBeCreated);
    }

    /**
     * Creates an EntityManager for testing purposes.
     *
     * NOTE: The created EntityManager will have its dependant DBAL parts completely
     * mocked out using a DriverMock, ConnectionMock, etc. These mocks can then
     * be configured in the tests to simulate the DBAL behavior that is desired
     * for a particular test.
     */
    protected function getTestEntityManager(
        mixed $conn = null,
        ?EventManager $eventManager = null,
        bool $withSharedMetadata = true
    ): EntityManagerMock {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCache($this->getMetadataCacheImpl($withSharedMetadata));
        $config->setMetadataDriverImpl(new AttributeDriver([]));
        $config->setQueryCache($this->getQueryCacheImpl());
        $config->setProxyDir($this->getProxyDir());
        $config->setProxyNamespace('Doctrine\Tests\Proxies');

        // The namespace of custom functions is hardcoded in \Oro\ORM\Query\AST\FunctionFactory::create, so we are
        // making our mock of 'CAST' available in Oro\ORM\Query\AST\Platform\Functions\Mock\ namespace:
        if (!class_exists('Oro\ORM\Query\AST\Platform\Functions\Mock\Cast', false)) {
            class_alias(
                \Oro\Component\Testing\Unit\ORM\Mocks\Cast::class,
                'Oro\ORM\Query\AST\Platform\Functions\Mock\Cast'
            );
        }
        $config->setCustomStringFunctions(['cast' => 'Oro\ORM\Query\AST\Functions\Cast']);

        if (null === $conn) {
            $conn = [
                'driverClass'  => DriverMock::class,
                'wrapperClass' => ConnectionMock::class,
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
     * Changes a connection object for the given entity manager.
     */
    protected function setDriverConnection(Connection $connection, EntityManagerMock $em)
    {
        /** @var DriverMock $driver */
        $driver = $em->getConnection()->getDriver();
        $driver->setDriverConnection($connection);

        // Close the connection to force reconnect with the new driver connection
        $em->getConnection()->close();
    }

    /**
     * Creates a mock for a statement which handles fetching the given records.
     */
    protected function createFetchStatementMock(
        ?array $records,
        array $params = [],
        array $types = [],
        ?int $affectedRowCount = null
    ): StatementMock|MockObject {
        $statement = $this->createMock(StatementMock::class);
        $result = $this->createFetchResultMock($records, $affectedRowCount);

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
                    ->method('execute')
                    ->willReturn($result);
            } else {
                $statement->expects($this->once())
                    ->method('execute')
                    ->with($params)
                    ->willReturn($result);
            }
        } else {
            $statement->expects($this->any())
                ->method('execute')
                ->willReturn($result);
        }

        return $statement;
    }

    /**
     * Creates a mock for a result which handles fetching the given records.
     * Use this when Connection::query() needs to return a Result directly.
     */
    protected function createFetchResultMock(
        ?array $records,
        ?int $affectedRowCount = null
    ): ResultMock|MockObject {
        $result = $this->createMock(ResultMock::class);

        if (null === $records) {
            $result->expects($this->never())
                ->method('fetchAssociative');
            $result->expects($this->any())
                ->method('fetchAllAssociative')
                ->willReturn([]);
            $result->expects($this->any())
                ->method('fetchFirstColumn')
                ->willReturn([]);
        } else {
            // DBAL 3.0: Doctrine ORM may call fetchAllAssociative() to get all rows at once
            $result->expects($this->any())
                ->method('fetchAllAssociative')
                ->willReturn($records);

            // DBAL 3.0: OR it may call fetchAssociative() in a loop
            $result->expects($this->any())
                ->method('fetchAssociative')
                ->will(new ConsecutiveCalls(array_merge($records, [false])));

            // DBAL 3.0: For fetchFirstColumn(), extract the first column from each record
            $firstColumn = array_map(function ($record) {
                return is_array($record) ? reset($record) : $record;
            }, $records);
            $result->expects($this->any())
                ->method('fetchFirstColumn')
                ->willReturn($firstColumn);
        }

        if (null !== $affectedRowCount) {
            $result->expects($this->once())
                ->method('rowCount')
                ->willReturn($affectedRowCount);
        }

        return $result;
    }

    /**
     * Creates a mock for a result which handles counting a number of records.
     * Use this when Connection::query() needs to return a Result directly.
     */
    protected function createCountResultMock(int $numberOfRecords): ResultMock|MockObject
    {
        $result = $this->createMock(ResultMock::class);

        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn($numberOfRecords);

        return $result;
    }

    /**
     * Creates a mock for 'Doctrine\DBAL\Driver\Connection' and sets it to the given entity manager.
     */
    protected function getDriverConnectionMock(EntityManagerMock $em): Connection|MockObject
    {
        $conn = $this->createMock(Connection::class);
        $this->setDriverConnection($conn, $em);

        return $conn;
    }

    /**
     * Creates a mock for a statement which handles counting a number of records.
     */
    protected function createCountStatementMock(int $numberOfRecords): StatementMock|MockObject
    {
        $countStatement = $this->createMock(StatementMock::class);
        $result = $this->createMock(ResultMock::class);

        $result->expects($this->once())
            ->method('fetchOne')
            ->willReturn($numberOfRecords);

        $countStatement->expects($this->any())
            ->method('execute')
            ->willReturn($result);

        return $countStatement;
    }

    protected function setQueryExpectation(
        Connection|MockObject $conn,
        string|Constraint $sql,
        ?array $records,
        array $params = [],
        array $types = [],
        ?int $affectedRowCount = null
    ): void {
        if ($params) {
            $stmt = $this->createFetchStatementMock($records, $params, $types, $affectedRowCount);
            $conn->expects($this->once())
                ->method('prepare')
                ->with($sql)
                ->willReturn($stmt);
        } else {
            // In DBAL 3.0, queries without params use query() method
            $result = $this->createFetchResultMock($records, $affectedRowCount);
            $conn->expects($this->once())
                ->method('query')
                ->with($sql)
                ->willReturn($result);
        }
    }

    protected function addQueryExpectation(
        string|Constraint $sql,
        ?array $records,
        array $params = [],
        array $types = [],
        ?int $affectedRowCount = null
    ): void {
        if ($params) {
            $stmt = $this->createFetchStatementMock($records, $params, $types, $affectedRowCount);
            $this->queryExpectations['prepare'][] = [$sql, $stmt];
        } else {
            $result = $this->createFetchResultMock($records, $affectedRowCount);
            $this->queryExpectations['query'][] = [$sql, $result];
        }
    }

    protected function applyQueryExpectations(Connection|MockObject $conn): void
    {
        if (!$this->queryExpectations) {
            throw new \LogicException('The addQueryExpectation() should be called before.');
        }

        $queryExpectations = $this->queryExpectations;
        $this->queryExpectations = [];

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

    private function getMetadataCacheImpl(bool $withSharedMetadata): CacheItemPoolInterface
    {
        if (!$withSharedMetadata) {
            // do not cache anything to avoid influence between tests
            return new NullAdapter();
        }

        if (null === $this->metadataCacheImpl) {
            $this->metadataCacheImpl = new ArrayAdapter(0, false);
        }

        return $this->metadataCacheImpl;
    }

    protected function getQueryCacheImpl(): CacheItemPoolInterface
    {
        // do not cache anything to avoid influence between tests
        return new NullAdapter();
    }
}
