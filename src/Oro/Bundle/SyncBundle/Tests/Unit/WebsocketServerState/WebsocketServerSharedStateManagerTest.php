<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\Tests\Unit\WebsocketServerState;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SyncBundle\WebsocketServerState\WebsocketServerSharedStateManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class WebsocketServerSharedStateManagerTest extends TestCase
{
    private Connection&MockObject $connection;
    private WebsocketServerSharedStateManager $manager;

    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->connection = $this->createMock(Connection::class);
        $doctrine->expects(self::any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->manager = new WebsocketServerSharedStateManager($doctrine);
    }

    public function testUpdateStateInsertsOrUpdatesDatabase(): void
    {
        $stateId = 'test_state_id';

        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->with(
                'INSERT INTO oro_sync_websocket_server_state (id, updated_at) VALUES (:id, :date)
                 ON CONFLICT (id) DO UPDATE SET updated_at = EXCLUDED.updated_at',
                self::callback(static function ($params) use ($stateId) {
                    return $params['id'] === $stateId
                        && $params['date'] instanceof \DateTime;
                }),
                [
                    'id' => Types::STRING,
                    'date' => Types::DATETIME_MUTABLE,
                ]
            );

        $result = $this->manager->updateState($stateId);

        self::assertEquals('UTC', $result->getTimezone()->getName());
    }

    public function testGetStateReturnsDateTimeWhenRecordExists(): void
    {
        $stateId = 'test_state_id';
        $dateString = '2024-01-15 10:30:00';

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $result = $this->createMock(Result::class);

        $this->connection->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('from')
            ->with('oro_sync_websocket_server_state')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('updated_at')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('id = :id')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('id', $stateId, Types::STRING)
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('executeQuery')
            ->willReturn($result);

        $result->expects(self::once())
            ->method('fetchOne')
            ->willReturn($dateString);

        $returnedDate = $this->manager->getState($stateId);

        self::assertInstanceOf(\DateTime::class, $returnedDate);
        self::assertEquals($dateString, $returnedDate->format('Y-m-d H:i:s'));
        self::assertEquals('UTC', $returnedDate->getTimezone()->getName());
    }

    public function testGetStateReturnsNullWhenRecordNotFound(): void
    {
        $stateId = 'test_state_id';

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $result = $this->createMock(Result::class);

        $this->connection->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('from')
            ->with('oro_sync_websocket_server_state')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('updated_at')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('id = :id')
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('id', $stateId, Types::STRING)
            ->willReturnSelf();

        $queryBuilder->expects(self::once())
            ->method('executeQuery')
            ->willReturn($result);

        $result->expects(self::once())
            ->method('fetchOne')
            ->willReturn(false);

        $returnedDate = $this->manager->getState($stateId);

        self::assertNull($returnedDate);
    }
}
