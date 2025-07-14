<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateExtendConfigMigrationQueryTest extends TestCase
{
    use TempDirExtension;

    private CommandExecutor&MockObject $commandExecutor;
    private string $temporaryFilePath;

    #[\Override]
    protected function setUp(): void
    {
        $this->commandExecutor = $this->createMock(CommandExecutor::class);

        $this->temporaryFilePath = $this->getTempDir('extend_config_migration')
            . DIRECTORY_SEPARATOR
            . 'test_options.bin';
    }

    public function testGetDescription(): void
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

    public function testExecute(): void
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
