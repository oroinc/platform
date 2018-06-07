<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Periodic;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Oro\Bundle\SyncBundle\Periodic\DbPingPeriodic;
use Psr\Log\LoggerInterface;

class DbPingPeriodicTest extends \PHPUnit_Framework_TestCase
{
    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var DbPingPeriodic */
    protected $dbPing;

    public function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->dbPing = new DbPingPeriodic($this->doctrine, $this->logger);
    }

    public function testGetTimeout()
    {
        self::assertSame(20, (new DbPingPeriodic($this->doctrine, $this->logger))->getTimeout());
        self::assertEquals(60, (new DbPingPeriodic($this->doctrine, $this->logger, 60))->getTimeout());
    }

    public function testTick()
    {
        $connection1 = $this->createMock(Connection::class);
        $connection1->expects($this->once())
            ->method('exec')
            ->with('SELECT 1');

        $connection2 = $this->createMock(Connection::class);
        $connection2->expects($this->once())
            ->method('exec')
            ->with('SELECT 1');

        $this->doctrine->expects($this->once())
            ->method('getConnections')
            ->willReturn([$connection1, $connection2]);

        $this->dbPing->tick();
    }

    public function testTickException()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('exec')
            ->willThrowException(new DBALException());

        $this->doctrine->expects($this->once())
            ->method('getConnections')
            ->willReturn([$connection]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Can\'t ping database connection');

        $this->dbPing->tick();
    }
}
