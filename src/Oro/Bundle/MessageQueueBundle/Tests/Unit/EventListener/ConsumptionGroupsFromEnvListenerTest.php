<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\EventListener;

use Oro\Bundle\MessageQueueBundle\Event\TransportConsumeMessagesCommandConsoleEvent;
use Oro\Bundle\MessageQueueBundle\EventListener\ConsumptionGroupsFromEnvListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsumptionGroupsFromEnvListenerTest extends TestCase
{
    private string|false $previousColumns;

    #[\Override]
    protected function setUp(): void
    {
        $this->previousColumns = getenv('COLUMNS');
        putenv('COLUMNS=80');
    }

    #[\Override]
    protected function tearDown(): void
    {
        putenv($this->previousColumns !== false ? 'COLUMNS=' . $this->previousColumns : 'COLUMNS');
    }

    public function testDoesNothingWhenConsumptionGroupsIsNull(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::never())
            ->method('setOption');
        $input->expects(self::never())
            ->method('setArgument');

        $listener = new ConsumptionGroupsFromEnvListener(null);
        $listener->onConsoleCommand(
            new TransportConsumeMessagesCommandConsoleEvent(
                $this->createMock(Command::class),
                $input,
                $output
            )
        );
    }

    public function testDoesNothingWhenConsumptionGroupsIsEmptyString(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::never())
            ->method('setOption');
        $input->expects(self::never())
            ->method('setArgument');

        $listener = new ConsumptionGroupsFromEnvListener('');
        $listener->onConsoleCommand(
            new TransportConsumeMessagesCommandConsoleEvent(
                $this->createMock(Command::class),
                $input,
                $output
            )
        );
    }

    public function testDoesNothingWhenQueueOptionIsAlreadyPassed(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::once())
            ->method('hasParameterOption')
            ->with('--queue')
            ->willReturn(true);
        $input->expects(self::never())
            ->method('setOption');
        $input->expects(self::never())
            ->method('setArgument');

        $listener = new ConsumptionGroupsFromEnvListener('{"group-a": {"oro.default": []}}');
        $listener->onConsoleCommand(
            new TransportConsumeMessagesCommandConsoleEvent(
                $this->createMock(Command::class),
                $input,
                $output
            )
        );
    }

    public function testDoesNothingWhenQueueArgumentIsNull(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::once())
            ->method('hasParameterOption')
            ->with('--queue')
            ->willReturn(false);
        $input->expects(self::once())
            ->method('getArgument')
            ->with('queue')
            ->willReturn(null);
        $input->expects(self::never())
            ->method('setOption');
        $input->expects(self::never())
            ->method('setArgument');

        $listener = new ConsumptionGroupsFromEnvListener('{"group-a": {"oro.default": []}}');
        $listener->onConsoleCommand(
            new TransportConsumeMessagesCommandConsoleEvent(
                $this->createMock(Command::class),
                $input,
                $output
            )
        );
    }

    public function testDoesNothingWhenQueueArgumentIsEmptyString(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::once())
            ->method('hasParameterOption')
            ->with('--queue')
            ->willReturn(false);
        $input->expects(self::once())
            ->method('getArgument')
            ->with('queue')
            ->willReturn('');
        $input->expects(self::never())
            ->method('setOption');
        $input->expects(self::never())
            ->method('setArgument');

        $listener = new ConsumptionGroupsFromEnvListener('{"group-a": {"oro.default": []}}');
        $listener->onConsoleCommand(
            new TransportConsumeMessagesCommandConsoleEvent(
                $this->createMock(Command::class),
                $input,
                $output
            )
        );
    }

    public function testDoesNothingWhenGroupNameNotFoundInConsumptionGroups(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::once())
            ->method('hasParameterOption')
            ->with('--queue')
            ->willReturn(false);
        $input->expects(self::once())
            ->method('getArgument')
            ->with('queue')
            ->willReturn('group-b');
        $input->expects(self::never())
            ->method('setOption');
        $input->expects(self::never())
            ->method('setArgument');

        $listener = new ConsumptionGroupsFromEnvListener('{"group-a": {"oro.default": []}}');
        $listener->onConsoleCommand(
            new TransportConsumeMessagesCommandConsoleEvent(
                $this->createMock(Command::class),
                $input,
                $output
            )
        );
    }

    public function testExpandsGroupWithEmptySettingsToPlainQueueNames(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::once())
            ->method('hasParameterOption')
            ->with('--queue')
            ->willReturn(false);
        $input->expects(self::once())
            ->method('getArgument')
            ->with('queue')
            ->willReturn('my-group');
        $input->expects(self::once())
            ->method('setOption')
            ->with('queue', ['oro.default', 'oro.system']);
        $input->expects(self::once())
            ->method('setArgument')
            ->with('queue', null);

        $json = '{"my-group": {"oro.default": [], "oro.system": []}}';
        $listener = new ConsumptionGroupsFromEnvListener($json);
        $listener->onConsoleCommand(
            new TransportConsumeMessagesCommandConsoleEvent(
                $this->createMock(Command::class),
                $input,
                $output
            )
        );
    }

    public function testExpandsGroupWithKeyValueSettingsToFormattedQueueOption(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::once())
            ->method('hasParameterOption')
            ->with('--queue')
            ->willReturn(false);
        $input->expects(self::once())
            ->method('getArgument')
            ->with('queue')
            ->willReturn('my-group');
        $input->expects(self::once())
            ->method('setOption')
            ->with('queue', ['name=oro.index,processor=acme.proc,weight=3']);
        $input->expects(self::once())
            ->method('setArgument')
            ->with('queue', null);

        $json = '{"my-group": {"oro.index": {"processor": "acme.proc", "weight": "3"}}}';
        $listener = new ConsumptionGroupsFromEnvListener($json);
        $listener->onConsoleCommand(
            new TransportConsumeMessagesCommandConsoleEvent(
                $this->createMock(Command::class),
                $input,
                $output
            )
        );
    }

    public function testExpandsGroupWithMixedSettings(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->expects(self::once())
            ->method('hasParameterOption')
            ->with('--queue')
            ->willReturn(false);
        $input->expects(self::once())
            ->method('getArgument')
            ->with('queue')
            ->willReturn('my-group');
        $input->expects(self::once())
            ->method('setOption')
            ->with('queue', ['oro.default', 'name=oro.index,processor=acme.proc,weight=3']);
        $input->expects(self::once())
            ->method('setArgument')
            ->with('queue', null);

        $json = '{"my-group": {"oro.default": [], "oro.index": {"processor": "acme.proc", "weight": "3"}}}';
        $listener = new ConsumptionGroupsFromEnvListener($json);
        $listener->onConsoleCommand(
            new TransportConsumeMessagesCommandConsoleEvent(
                $this->createMock(Command::class),
                $input,
                $output
            )
        );
    }

    public function testOutputsNoteMessageContainingGroupAndQueueNames(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = new BufferedOutput();

        $input->expects(self::once())
            ->method('hasParameterOption')
            ->with('--queue')
            ->willReturn(false);
        $input->expects(self::once())
            ->method('getArgument')
            ->with('queue')
            ->willReturn('my-group');

        $json = '{"my-group": {"oro.default": [], "oro.system": []}}';
        $listener = new ConsumptionGroupsFromEnvListener($json);
        $listener->onConsoleCommand(
            new TransportConsumeMessagesCommandConsoleEvent(
                $this->createMock(Command::class),
                $input,
                $output
            )
        );

        $outputText = $output->fetch();

        self::assertEquals(
            "\n" .
            " ! [NOTE] Argument \"my-group\" is recognized as a consumption group defined in   \n" .
            " !        the ORO_MQ_CONSUMPTION_GROUPS environment variable. Consumption queues\n" .
            " !        have been switched to: oro.default, oro.system.                       \n" .
            "\n",
            $outputText
        );
    }
}
