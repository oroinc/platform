<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Periodic;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Statement;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SyncBundle\Periodic\DbPingPeriodic;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DbPingPeriodicTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private ManagerRegistry|MockObject $doctrine;

    private DbPingPeriodic $dbPing;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->dbPing = new DbPingPeriodic($this->doctrine);

        $this->setUpLoggerMock($this->dbPing);
    }

    public function testGetTimeout(): void
    {
        self::assertSame(20, (new DbPingPeriodic($this->doctrine))->getTimeout());
        self::assertEquals(60, (new DbPingPeriodic($this->doctrine, 60))->getTimeout());
    }

    public function testTick(): void
    {
        $statement1 = $this->createMock(Statement::class);
        $statement1->expects(self::once())
            ->method('executeQuery');

        $connection1 = $this->createMock(Connection::class);
        $connection1->expects(self::once())
            ->method('prepare')
            ->with('SELECT 1')
            ->willReturn($statement1);

        $connection2 = $this->createMock(Connection::class);
        $connection2->expects(self::never())->method('prepare');

        $this->doctrine->expects(self::once())
            ->method('getConnections')
            ->willReturn([
                'default' => $connection1,
                'unexpected' => $connection2,
            ]);

        $this->dbPing->tick();
    }

    public function testTickException(): void
    {
        $statement1 = $this->createMock(Statement::class);
        $statement1->expects(self::once())
            ->method('executeQuery')
            ->willThrowException(new DBALException());

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('prepare')
            ->with('SELECT 1')
            ->willReturn($statement1);

        $this->doctrine->expects(self::once())
            ->method('getConnections')
            ->willReturn(['default' => $connection]);

        $this->assertLoggerErrorMethodCalled();

        $this->expectException(DBALException::class);

        $this->dbPing->tick();
    }
}
