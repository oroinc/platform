<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Psr\Log\LoggerInterface;

class RemoveFieldQueryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $commandExecutor;

    /** @var  LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var  Connection|\PHPUnit\Framework\MockObject\MockObject */
    protected $connector;

    /** @var  Statement|\PHPUnit\Framework\MockObject\MockObject */
    protected $statement;

    protected function setUp()
    {
        $this->connector = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->getMock();
        $this->statement = $this->getMockBuilder('\Doctrine\DBAL\Driver\Statement')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testExecuteConfigFieldIsAbsent()
    {
        $migration = new RemoveFieldQuery('TestClassName', 'TestFieldName');
        $migration->setConnection($this->connector);

        $this->connector->expects(self::once())->method('fetchAssoc')->willReturn(null);
        $this->connector->expects(self::never())->method('prepare');
        $migration->execute($this->logger);
    }

    public function testExecute()
    {
        $migration = new RemoveFieldQuery('TestClassName', 'TestFieldName');
        $migration->setConnection($this->connector);

        $this->connector->expects(self::once())->method('fetchAssoc')->willReturn(['id' => 1]);
        $this->connector->expects(self::once())->method('prepare')->willReturn($this->statement);
        $migration->execute($this->logger);
    }
}
