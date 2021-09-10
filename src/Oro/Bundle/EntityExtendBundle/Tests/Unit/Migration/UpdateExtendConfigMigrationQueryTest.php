<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Component\Testing\TempDirExtension;

class UpdateExtendConfigMigrationQueryTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var CommandExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $commandExecutor;

    /** @var string */
    private $temporaryFilePath;

    protected function setUp(): void
    {
        $this->commandExecutor = $this->createMock(CommandExecutor::class);

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
            ->willReturnCallback(function ($command, $params, $logger) {
                if ($logger instanceof ArrayLogger) {
                    $logger->info('test message');
                }

                return 0;
            });

        $migrationQuery = new UpdateExtendConfigMigrationQuery(
            $options,
            $this->commandExecutor,
            $this->temporaryFilePath
        );

        self::assertEquals(['test message'], $migrationQuery->getDescription());
        self::assertFileDoesNotExist($this->temporaryFilePath);
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
            ->willReturnCallback(function ($command, $params, $logger) {
                if ($logger instanceof ArrayLogger) {
                    $logger->info('test message');
                }

                return 0;
            });

        $migrationQuery = new UpdateExtendConfigMigrationQuery(
            $options,
            $this->commandExecutor,
            $this->temporaryFilePath
        );

        $migrationQuery->execute($logger);

        self::assertEquals(['test message'], $logger->getMessages());
        self::assertFileDoesNotExist($this->temporaryFilePath);
    }
}
