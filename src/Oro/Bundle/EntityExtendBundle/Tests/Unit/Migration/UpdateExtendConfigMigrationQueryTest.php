<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;

class UpdateExtendConfigMigrationQueryTest extends \PHPUnit_Framework_TestCase
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
        $optionsPath = realpath(__DIR__ . '/../Fixtures') . '/test_options.yml';
        $options     = ['test'];

        $this->commandExecutor->expects($this->once())
            ->method('runCommand')
            ->with(
                'oro:entity-extend:migration:update-config',
                ['--dry-run' => true, '--ignore-errors' => true]
            )
            ->will(
                $this->returnCallback(
                    function ($command, $params, $logger) {
                        if ($logger instanceof ArrayLogger) {
                            $logger->notice('test message');
                        }

                        return 0;
                    }
                )
            );

        $migrationQuery = new UpdateExtendConfigMigrationQuery(
            $options,
            $this->commandExecutor,
            $optionsPath
        );

        $this->assertEquals(['test message'], $migrationQuery->getDescription());
    }

    public function testExecute()
    {
        $logger = new ArrayLogger();
        $optionsPath = realpath(__DIR__ . '/../Fixtures') . '/test_options.yml';
        $options     = ['test'];

        $this->commandExecutor->expects($this->once())
            ->method('runCommand')
            ->with(
                'oro:entity-extend:migration:update-config',
                []
            )
            ->will(
                $this->returnCallback(
                    function ($command, $params, $logger) {
                        if ($logger instanceof ArrayLogger) {
                            $logger->notice('test message');
                        }

                        return 0;
                    }
                )
            );

        $migrationQuery = new UpdateExtendConfigMigrationQuery(
            $options,
            $this->commandExecutor,
            $optionsPath
        );

        $migrationQuery->execute($logger);

        $this->assertEquals(['test message'], $logger->getMessages());

    }
}
