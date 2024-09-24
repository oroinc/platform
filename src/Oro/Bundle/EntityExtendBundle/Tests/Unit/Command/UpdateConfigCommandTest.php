<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Command;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Command\UpdateConfigCommand;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Component\Testing\Command\CommandTestingTrait;

class UpdateConfigCommandTest extends \PHPUnit\Framework\TestCase
{
    use CommandTestingTrait;

    /** @var ExtendConfigDumper|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigDumper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var UpdateConfigCommand */
    private $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->extendConfigDumper = $this->createMock(ExtendConfigDumper::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->command = new UpdateConfigCommand($this->extendConfigDumper, $this->configManager);
    }

    public function testExecuteWithoutForce()
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

    public function testExecuteWithForce()
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
