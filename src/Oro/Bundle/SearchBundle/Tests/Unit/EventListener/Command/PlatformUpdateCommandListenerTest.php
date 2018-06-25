<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener\Command;

use Oro\Bundle\InstallerBundle\Command\PlatformUpdateCommand;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\InstallerBundle\InstallerEvent;
use Oro\Bundle\SearchBundle\EventListener\Command\PlatformUpdateCommandListener;
use Oro\Bundle\SearchBundle\Provider\Console\SearchReindexationGlobalOptionsProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PlatformUpdateCommandListenerTest extends \PHPUnit\Framework\TestCase
{
    const COMMAND_NAME = 'test:command';

    /** @var Command|\PHPUnit\Framework\MockObject\MockObject */
    protected $command;

    /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $input;

    /** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $output;

    /** @var CommandExecutor|\PHPUnit\Framework\MockObject\MockObject */
    protected $commandExecutor;

    /** @var InstallerEvent */
    protected $event;

    protected function setUp()
    {
        $this->command = $this->createMock(Command::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->commandExecutor = $this->createMock(CommandExecutor::class);

        $this->event = new InstallerEvent($this->command, $this->input, $this->output, $this->commandExecutor);
    }

    public function testOnAfterDatabasePreparationNotSupportedCommand()
    {
        $this->command->expects($this->once())
            ->method('getName')
            ->willReturn('some:command');

        $this->input->expects($this->never())
            ->method($this->anything());

        $this->output->expects($this->never())
            ->method($this->anything());

        $this->commandExecutor->expects($this->never())
            ->method($this->anything());

        $listener = new PlatformUpdateCommandListener('test:command');
        $listener->onAfterDatabasePreparation($this->event);
    }

    /**
     * @dataProvider onAfterDatabasePreparationProvider
     *
     * @param bool $isSkip
     * @param bool $isScheduled
     * @param string $expectedMessage
     */
    public function testOnAfterDatabasePreparation(bool $isSkip, bool $isScheduled, string $expectedMessage)
    {
        $this->command->expects($this->once())
            ->method('getName')
            ->willReturn(PlatformUpdateCommand::NAME);

        $this->input->expects($this->any())
            ->method('hasOption')
            ->willReturn(true);

        $this->input->expects($this->any())
            ->method('getOption')
            ->willReturnMap(
                [
                    [SearchReindexationGlobalOptionsProvider::SKIP_REINDEXATION_OPTION_NAME, $isSkip],
                    [SearchReindexationGlobalOptionsProvider::SCHEDULE_REINDEXATION_OPTION_NAME, $isScheduled],
                ]
            );

        $this->output->expects($this->once())
            ->method('writeln')
            ->with([$expectedMessage, '']);

        $expectedParams = ['--scheduled' => true, '--process-isolation' => true];
        if (!$isScheduled) {
            unset($expectedParams['--scheduled']);
        }

        $this->commandExecutor->expects($isSkip ? $this->never() : $this->once())
            ->method('runCommand')
            ->with(self::COMMAND_NAME, $expectedParams);

        $listener = new PlatformUpdateCommandListener(self::COMMAND_NAME);
        $listener->onAfterDatabasePreparation($this->event);
    }

    /**
     * @return array
     */
    public function onAfterDatabasePreparationProvider(): array
    {
        return [
            'not skipped, not scheduled re-indexation' => [
                'isSkip' => false,
                'isScheduled' => false,
                'expectedMessage' => '<comment>Full re-indexation with "test:command" command finished</comment>',
            ],
            'skipped re-indexation' => [
                'isSkip' => true,
                'isScheduled' => false,
                'expectedMessages' => '<comment>Full re-indexation with "test:command" command skipped</comment>',
            ],
            'not skipped, scheduled re-indexation' => [
                'isSkip' => false,
                'isScheduled' => true,
                'expectedMessages' => '<comment>Full re-indexation with "test:command" command scheduled</comment>',
            ],
            'skipped, scheduled re-indexation' => [
                'isSkip' => true,
                'isScheduled' => true,
                'expectedMessages' => '<comment>Full re-indexation with "test:command" command skipped</comment>',
            ],
        ];
    }
}
