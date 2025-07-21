<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigrationQuery;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UpdateEntityConfigMigrationQueryTest extends TestCase
{
    private CommandExecutor&MockObject $commandExecutor;

    #[\Override]
    protected function setUp(): void
    {
        $this->commandExecutor = $this->createMock(CommandExecutor::class);
    }

    public function testGetDescription(): void
    {
        $migrationQuery = new UpdateEntityConfigMigrationQuery(
            $this->commandExecutor
        );

        $this->assertEquals('Update entity configs', $migrationQuery->getDescription());
    }

    public function testExecute(): void
    {
        $logger = new ArrayLogger();

        $this->commandExecutor->expects($this->once())
            ->method('runCommand')
            ->with('oro:entity-config:update', [], $logger)
            ->willReturnCallback(function ($command, $params, $logger) {
                if ($logger instanceof LoggerInterface) {
                    $logger->info('ok');
                }

                return 0;
            });

        $migrationQuery = new UpdateEntityConfigMigrationQuery($this->commandExecutor);

        $migrationQuery->execute($logger);

        $this->assertEquals(['ok'], $logger->getMessages());
    }
}
