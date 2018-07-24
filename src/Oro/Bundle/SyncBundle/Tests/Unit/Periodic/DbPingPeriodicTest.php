<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Periodic;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Statement;
use Oro\Bundle\SyncBundle\Periodic\DbPingPeriodic;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class DbPingPeriodicTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var DbPingPeriodic */
    private $dbPing;

    public function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->dbPing = new DbPingPeriodic($this->doctrine);

        $this->setUpLoggerMock($this->dbPing);
    }

    public function testGetTimeout()
    {
        self::assertSame(20, (new DbPingPeriodic($this->doctrine))->getTimeout());
        self::assertEquals(60, (new DbPingPeriodic($this->doctrine, 60))->getTimeout());
    }

    public function testTick()
    {
        $statement1 = $this->createMock(Statement::class);
        $statement1->expects(self::once())
            ->method('execute');

        $connection1 = $this->createMock(Connection::class);
        $connection1->expects(self::once())
            ->method('prepare')
            ->with('SELECT 1')
            ->willReturn($statement1);

        $statement2 = $this->createMock(Statement::class);
        $statement2->expects(self::once())
            ->method('execute');

        $connection2 = $this->createMock(Connection::class);
        $connection2->expects(self::once())
            ->method('prepare')
            ->with('SELECT 1')
            ->willReturn($statement2);

        $this->doctrine->expects(self::once())
            ->method('getConnections')
            ->willReturn([$connection1, $connection2]);

        $this->dbPing->tick();
    }

    public function testTickException()
    {
        $statement1 = $this->createMock(Statement::class);
        $statement1->expects(self::once())
            ->method('execute')
            ->willThrowException(new DBALException());

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('prepare')
            ->with('SELECT 1')
            ->willReturn($statement1);

        $this->doctrine->expects(self::once())
            ->method('getConnections')
            ->willReturn([$connection]);

        $this->assertLoggerErrorMethodCalled();

        $this->dbPing->tick();
    }
}
