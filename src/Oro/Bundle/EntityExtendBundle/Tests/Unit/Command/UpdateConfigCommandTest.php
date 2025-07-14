<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Command;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Command\UpdateConfigCommand;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Component\Testing\Command\CommandTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateConfigCommandTest extends TestCase
{
    use CommandTestingTrait;

    private ExtendConfigDumper&MockObject $extendConfigDumper;
    private ConfigManager&MockObject $configManager;
    private UpdateConfigCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->extendConfigDumper = $this->createMock(ExtendConfigDumper::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->command = new UpdateConfigCommand($this->extendConfigDumper, $this->configManager);
    }

    public function testExecuteWithoutForce(): void
    {
        $this->extendConfigDumper->expects(self::never())
            ->method('updateConfig');
        $this->configManager->expects(self::never())
            ->method('useLocalCacheOnly');

        $commandOutput = $this->doExecuteCommand($this->command);

        $this->assertOutputContains(
            $commandOutput,
            'This is an internal command. Please do not run it manually.'
        );
    }

    public function testExecuteWithForce(): void
    {
        $this->extendConfigDumper->expects(self::once())
            ->method('updateConfig');
        $this->configManager->expects(self::once())
            ->method('useLocalCacheOnly');

        $commandOutput = $this->doExecuteCommand($this->command, ['--force' => true]);

        $this->assertOutputNotContains(
            $commandOutput,
            'This is an internal command. Please do not run it manually.'
        );
    }
}
