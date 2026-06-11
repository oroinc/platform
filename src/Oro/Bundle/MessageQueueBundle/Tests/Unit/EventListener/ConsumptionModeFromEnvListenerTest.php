<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\EventListener;

use Oro\Bundle\MessageQueueBundle\Event\TransportConsumeMessagesCommandConsoleEvent;
use Oro\Bundle\MessageQueueBundle\EventListener\ConsumptionModeFromEnvListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsumptionModeFromEnvListenerTest extends TestCase
{
    private InputInterface&MockObject $input;
    private string|false $previousColumns;

    #[\Override]
    protected function setUp(): void
    {
        $this->input = $this->createMock(InputInterface::class);
        $this->previousColumns = getenv('COLUMNS');
        putenv('COLUMNS=80');
    }

    #[\Override]
    protected function tearDown(): void
    {
        putenv($this->previousColumns !== false ? 'COLUMNS=' . $this->previousColumns : 'COLUMNS');
    }

    public function testDoesNothingWhenCommandHasNoModeOption(): void
    {
        $listener = new ConsumptionModeFromEnvListener('default');

        $command = $this->createMock(Command::class);
        $command->expects(self::once())
            ->method('getDefinition')
            ->willReturn(new InputDefinition());

        $output = $this->createMock(OutputInterface::class);
        $event = new TransportConsumeMessagesCommandConsoleEvent($command, $this->input, $output);

        $this->input->expects(self::never())
            ->method('setOption');

        $listener->onConsoleCommand($event);
    }

    public function testDoesNothingWhenConsumptionModeIsNull(): void
    {
        $listener = new ConsumptionModeFromEnvListener(null);

        $definition = new InputDefinition([new InputOption('mode', null, InputOption::VALUE_OPTIONAL)]);
        $command = $this->createMock(Command::class);
        $command->expects(self::once())
            ->method('getDefinition')
            ->willReturn($definition);

        $output = $this->createMock(OutputInterface::class);
        $event = new TransportConsumeMessagesCommandConsoleEvent($command, $this->input, $output);

        $this->input->expects(self::never())
            ->method('setOption');

        $listener->onConsoleCommand($event);
    }

    public function testDoesNothingWhenConsumptionModeIsEmptyString(): void
    {
        $listener = new ConsumptionModeFromEnvListener('');

        $definition = new InputDefinition([new InputOption('mode', null, InputOption::VALUE_OPTIONAL)]);
        $command = $this->createMock(Command::class);
        $command->expects(self::once())
            ->method('getDefinition')
            ->willReturn($definition);

        $output = $this->createMock(OutputInterface::class);
        $event = new TransportConsumeMessagesCommandConsoleEvent($command, $this->input, $output);

        $this->input->expects(self::never())
            ->method('setOption');

        $listener->onConsoleCommand($event);
    }

    public function testDoesNothingWhenModeOptionIsAlreadyPassedExplicitly(): void
    {
        $listener = new ConsumptionModeFromEnvListener('default');

        $definition = new InputDefinition([new InputOption('mode', null, InputOption::VALUE_OPTIONAL)]);
        $command = $this->createMock(Command::class);
        $command->expects(self::once())
            ->method('getDefinition')
            ->willReturn($definition);

        $output = $this->createMock(OutputInterface::class);
        $event = new TransportConsumeMessagesCommandConsoleEvent($command, $this->input, $output);

        $this->input->expects(self::once())
            ->method('hasParameterOption')
            ->with('--mode')
            ->willReturn(true);

        $this->input->expects(self::never())
            ->method('setOption');

        $listener->onConsoleCommand($event);
    }

    public function testSetsModeOptionFromEnvVar(): void
    {
        $listener = new ConsumptionModeFromEnvListener('strict-priority-interleaving');

        $definition = new InputDefinition([new InputOption('mode', null, InputOption::VALUE_OPTIONAL)]);
        $command = $this->createMock(Command::class);
        $command->expects(self::once())
            ->method('getDefinition')
            ->willReturn($definition);

        $output = $this->createMock(OutputInterface::class);
        $output->method('getVerbosity')
            ->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $output->method('isDecorated')
            ->willReturn(false);
        $event = new TransportConsumeMessagesCommandConsoleEvent($command, $this->input, $output);

        $this->input->expects(self::once())
            ->method('hasParameterOption')
            ->with('--mode')
            ->willReturn(false);

        $this->input->expects(self::once())
            ->method('setOption')
            ->with('mode', 'strict-priority-interleaving');

        $listener->onConsoleCommand($event);
    }

    public function testOutputsNoteMessageContainingModeName(): void
    {
        $listener = new ConsumptionModeFromEnvListener('strict-priority-interleaving');

        $definition = new InputDefinition([new InputOption('mode', null, InputOption::VALUE_OPTIONAL)]);
        $command = $this->createMock(Command::class);
        $command->expects(self::once())
            ->method('getDefinition')
            ->willReturn($definition);

        $output = new BufferedOutput();
        $event = new TransportConsumeMessagesCommandConsoleEvent($command, $this->input, $output);

        $this->input->expects(self::once())
            ->method('hasParameterOption')
            ->with('--mode')
            ->willReturn(false);

        $this->input->expects(self::once())
            ->method('setOption')
            ->with('mode', 'strict-priority-interleaving');

        $listener->onConsoleCommand($event);

        self::assertEquals(
            "\n" .
            " ! [NOTE] Consumption mode set to \"strict-priority-interleaving\" based on the   \n" .
            " !        ORO_MQ_CONSUMPTION_MODE environment variable.                         \n" .
            "\n",
            $output->fetch()
        );
    }
}
