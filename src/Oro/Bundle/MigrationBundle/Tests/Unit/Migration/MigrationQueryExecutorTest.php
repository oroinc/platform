<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Oro\Bundle\MigrationBundle\Migration\MigrationQueryExecutor;

class MigrationQueryExecutorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var MigrationQueryExecutor */
    protected $executor;

    protected function setUp()
    {
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger     = $this->getMock('Psr\Log\LoggerInterface');
        $this->executor   = new MigrationQueryExecutor($this->connection);
        $this->executor->setLogger($this->logger);
    }

    public function testGetConnection()
    {
        $this->assertSame($this->connection, $this->executor->getConnection());
    }

    public function testExecuteSql()
    {
        $query = 'DELETE FROM some_table';

        $this->logger->expects($this->once())
            ->method('info')
            ->with($query);
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($query);

        $this->executor->execute($query, false);
    }

    public function testExecuteSqlDryRun()
    {
        $query = 'DELETE FROM some_table';

        $this->logger->expects($this->once())
            ->method('info')
            ->with($query);
        $this->connection->expects($this->never())
            ->method('executeQuery');

        $this->executor->execute($query, true);
    }

    public function testExecuteMigrationQuery()
    {
        $query = $this
            ->getMockForAbstractClass(
                'Oro\Bundle\MigrationBundle\Migration\MigrationQuery',
                [],
                '',
                true,
                true,
                true,
                ['setConnection', 'execute']
            )
        ;

        $query->expects($this->never())
            ->method('setConnection');
        $query->expects($this->once())
            ->method('execute')
            ->with($this->identicalTo($this->logger));

        $this->executor->execute($query, false);
    }

    public function testExecuteConnectionAwareMigrationQuery()
    {
        $query = $this->getMockBuilder('Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery')
            ->disableOriginalConstructor()
            ->getMock();

        $query->expects($this->once())
            ->method('setConnection')
            ->with($this->identicalTo($this->connection));
        $query->expects($this->once())
            ->method('execute')
            ->with($this->identicalTo($this->logger));

        $this->executor->execute($query, false);
    }

    public function testExecuteMigrationQueryDryRun()
    {
        $queryDescription = 'test query';

        $query = $this->getMock('Oro\Bundle\MigrationBundle\Migration\MigrationQuery');

        $query->expects($this->once())
            ->method('getDescription')
            ->will($this->returnValue($queryDescription));

        $this->logger->expects($this->once())
            ->method('info')
            ->with($queryDescription);

        $query->expects($this->never())
            ->method('execute');

        $this->executor->execute($query, true);
    }

    public function testExecuteMigrationQueryDryRunArrayDescription()
    {
        $queryDescription = ['test query 1', 'test query 2'];

        $query = $this->getMock('Oro\Bundle\MigrationBundle\Migration\MigrationQuery');

        $query->expects($this->once())
            ->method('getDescription')
            ->will($this->returnValue($queryDescription));

        $this->logger->expects($this->at(0))
            ->method('info')
            ->with($queryDescription[0]);
        $this->logger->expects($this->at(1))
            ->method('info')
            ->with($queryDescription[1]);

        $query->expects($this->never())
            ->method('execute');

        $this->executor->execute($query, true);
    }

    public function testExecuteMigrationQueryDryRunEmptyDescription()
    {
        $queryDescription = null;

        $query = $this->getMock('Oro\Bundle\MigrationBundle\Migration\MigrationQuery');

        $query->expects($this->once())
            ->method('getDescription')
            ->will($this->returnValue($queryDescription));

        $this->logger->expects($this->never())
            ->method('info');

        $query->expects($this->never())
            ->method('execute');

        $this->executor->execute($query, true);
    }
}
