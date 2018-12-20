<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Component\Testing\TempDirExtension;

class UpdateExtendConfigMigrationQueryTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $commandExecutor;

    /** @var string */
    protected $temporaryFilePath;

    protected function setUp()
    {
        $this->commandExecutor = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->temporaryFilePath = $this->getTempDir('extend_config_migration')
            . DIRECTORY_SEPARATOR
            . 'test_options.bin';
    }

    public function testGetDescription()
    {
        $options = ['test'];

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
                            $logger->info('test message');
                        }

                        return 0;
                    }
                )
            );

        $migrationQuery = new UpdateExtendConfigMigrationQuery(
            $options,
            $this->commandExecutor,
            $this->temporaryFilePath
        );

        self::assertEquals(['test message'], $migrationQuery->getDescription());
        self::assertFileNotExists($this->temporaryFilePath);
    }

    public function testExecute()
    {
        $logger = new ArrayLogger();
        $options = ['test'];

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
                            $logger->info('test message');
                        }

                        return 0;
                    }
                )
            );

        $migrationQuery = new UpdateExtendConfigMigrationQuery(
            $options,
            $this->commandExecutor,
            $this->temporaryFilePath
        );

        $migrationQuery->execute($logger);

        self::assertEquals(['test message'], $logger->getMessages());
        self::assertFileNotExists($this->temporaryFilePath);
    }
}
