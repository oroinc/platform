<?php

namespace Oro\Component\Testing\Unit\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Driver\Connection;
use Oro\Component\Testing\TempDirExtension;
use Oro\Component\Testing\Unit\ORM\Mocks\ConnectionMock;
use Oro\Component\Testing\Unit\ORM\Mocks\DriverMock;
use Oro\Component\Testing\Unit\ORM\Mocks\EntityManagerMock;
use Oro\Component\Testing\Unit\ORM\Mocks\FetchIterator;
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
        EventManager $eventManager = null,
        bool $withSharedMetadata = true
    ): EntityManagerMock {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCache($this->getMetadataCacheImpl($withSharedMetadata));
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver([], true));
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
        if (null === $records) {
            $statement->expects($this->never())
                ->method('fetch');
        } else {
            $statement->expects($this->exactly(count($records) + 1))
                ->method('fetch')
                ->will(new ConsecutiveCalls(array_merge($records, [false])));
        }
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
            if (null !== $affectedRowCount) {
                $statement->expects($this->once())
                    ->method('rowCount')
                    ->willReturn($affectedRowCount);
            }
        }

        return $statement;
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
        $countStatement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($numberOfRecords);

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
        $stmt = $this->createFetchStatementMock($records, $params, $types, $affectedRowCount);
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

    protected function addQueryExpectation(
        string|Constraint $sql,
        ?array $records,
        array $params = [],
        array $types = [],
        ?int $affectedRowCount = null
    ): void {
        $stmt = $this->createFetchStatementMock($records, $params, $types, $affectedRowCount);
        if ($params) {
            $this->queryExpectations['prepare'][] = [$sql, $stmt];
        } else {
            $this->queryExpectations['query'][] = [$sql, $stmt];
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
