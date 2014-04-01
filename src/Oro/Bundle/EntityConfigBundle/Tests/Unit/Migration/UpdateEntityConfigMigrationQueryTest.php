<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Psr\Log\LoggerInterface;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;

class UpdateEntityConfigMigrationQueryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $commandExecutor;

    protected function setUp()
    {
        $this->commandExecutor = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetDescription()
    {
        $migrationQuery = new UpdateEntityConfigMigrationQuery(
            $this->commandExecutor
        );

        $this->assertEquals('Update entity configs', $migrationQuery->getDescription());
    }

    public function testExecute()
    {
        $logger = new ArrayLogger();

        $this->commandExecutor
            ->expects($this->once())
            ->method('runCommand')
            ->with(
                'oro:entity-config:update',
                ['--process-timeout' => 300],
                $logger
            )
            ->will(
                $this->returnCallback(
                    function ($command, $params, $logger) {
                        if ($logger instanceof LoggerInterface) {
                            $logger->notice('ok');
                        }

                        return 0;
                    }
                )
            );

        $migrationQuery = new UpdateEntityConfigMigrationQuery($this->commandExecutor);

        $migrationQuery->execute($logger);

        $this->assertEquals(['ok'], $logger->getMessages());
    }
}
