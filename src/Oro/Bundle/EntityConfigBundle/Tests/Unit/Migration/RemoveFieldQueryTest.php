<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Psr\Log\LoggerInterface;

class RemoveFieldQueryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $commandExecutor;

    /** @var  LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var  Connection|\PHPUnit_Framework_MockObject_MockObject */
    protected $connector;

    /** @var  Statement|\PHPUnit_Framework_MockObject_MockObject */
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
