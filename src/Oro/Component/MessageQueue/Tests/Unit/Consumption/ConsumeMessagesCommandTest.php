<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Bundle\MessageQueueBundle\Job\JobManager;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueIterator\DefaultQueueIterator;
use Oro\Component\MessageQueue\Consumption\QueueIterator\QueueIteratorFactoryRegistry;
use Oro\Component\MessageQueue\Consumption\QueueIterator\StrictPriorityInterleavingQueueIterator;
use Oro\Component\MessageQueue\Consumption\QueueOptionValueParser;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ConsumeMessagesCommandTest extends TestCase
{
    private string|false $previousColumns;
    private QueueConsumer&MockObject $consumer;
    private QueueOptionValueParser&MockObject $queueOptionValueParser;
    private ConsumeMessagesCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->previousColumns = getenv('COLUMNS');
        putenv('COLUMNS=80');

        $this->consumer = $this->createMock(QueueConsumer::class);
        $this->jobManager = $this->createMock(JobManager::class);

        $this->queueIteratorFactoryRegistry = $this->createMock(QueueIteratorFactoryRegistry::class);
        $this->queueIteratorFactoryRegistry
            ->expects(self::any())
            ->method('getConsumptionModes')
            ->willReturn([DefaultQueueIterator::NAME, StrictPriorityInterleavingQueueIterator::NAME]);

        $this->queueOptionValueParser = $this->createMock(QueueOptionValueParser::class);

        $this->command = new ConsumeMessagesCommand($this->consumer);
    }

    #[\Override]
    protected function tearDown(): void
    {
        putenv($this->previousColumns !== false ? 'COLUMNS=' . $this->previousColumns : 'COLUMNS');
    }

    public function testShouldHaveCommandName(): void
    {
        self::assertEquals('oro:message-queue:transport:consume', $this->command->getName());
    }

    public function testShouldHaveExpectedOptions(): void
    {
        $options = $this->command->getDefinition()->getOptions();

        self::assertCount(8, $options);
        self::assertArrayHasKey('memory-limit', $options);
        self::assertArrayHasKey('message-limit', $options);
        self::assertArrayHasKey('time-limit', $options);
        self::assertArrayHasKey('object-limit', $options);
        self::assertArrayHasKey('gc-limit', $options);
        self::assertArrayHasKey('stop-when-unique-jobs-processed', $options);
        self::assertArrayHasKey('mode', $options);
        self::assertArrayHasKey('queue', $options);
    }

    public function testShouldHaveExpectedAttributes(): void
    {
        $arguments = $this->command->getDefinition()->getArguments();

        self::assertCount(2, $arguments);
        self::assertArrayHasKey('processor-service', $arguments);
        self::assertArrayHasKey('queue', $arguments);
    }

    public function testModeOptionDefaultIsDefault(): void
    {
        $modeOption = $this->command->getDefinition()->getOption('mode');

        self::assertSame(DefaultQueueIterator::NAME, $modeOption->getDefault());
    }

    public function testShouldExecuteConsumption(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('close');

        $this->consumer->expects(self::once())
            ->method('setConsumptionMode')
            ->with(DefaultQueueIterator::NAME);
        $this->consumer->expects(self::once())
            ->method('bindQueue')
            ->with('queue-name', [QueueConsumer::PROCESSOR => 'processor-service'])
            ->willReturnSelf();
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $tester = new CommandTester($this->command);
        $tester->execute(['queue' => 'queue-name', 'processor-service' => 'processor-service']);
    }

    public function testExecuteReturnsFailureForUnsupportedMode(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $this->consumer->expects(self::never())
            ->method('consume');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['queue' => 'q1', '--mode' => 'noop']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertEquals(
            "\n" .
            " [ERROR] Unknown consumption mode \"noop\". Supported modes: default,             \n" .
            "         strict-priority-interleaving                                           \n" .
            "\n",
            $tester->getDisplay()
        );
    }

    public function testExecuteReturnsFailureWhenQueueListEmpty(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $this->consumer->expects(self::never())
            ->method('consume');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['queue' => ',,,,', 'processor-service' => 'processor-service']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertEquals(
            "\n" .
            " [ERROR] The \"queue\" argument must contain at least one queue name when used in \n" .
            "         short notation. Expected format: \"oro.default\" or                      \n" .
            "         \"oro.default,oro.system\".                                              \n" .
            "\n",
            $tester->getDisplay()
        );
    }

    public function testExecuteReturnsFailureWhenBothQueueArgAndOptionProvided(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $this->consumer->expects(self::never())
            ->method('consume');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['queue' => 'q1', '--queue' => ['q2']]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertEquals(
            "\n" .
            " [ERROR] Cannot use both the \"queue\" positional argument and the \"--queue\"      \n" .
            "         option at the same time. Use one notation or the other.                \n" .
            "\n",
            $tester->getDisplay()
        );
    }

    public function testExecuteReturnsFailureWhenNeitherQueueArgNorOptionProvided(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $this->consumer->expects(self::never())
            ->method('consume');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertEquals(
            "\n" .
            " [ERROR] You must provide queue names either via the \"queue\" argument (short    \n" .
            "         notation) or via the \"--queue\" option (long notation).                 \n" .
            "\n",
            $tester->getDisplay()
        );
    }

    public function testExecuteReturnsFailureWhenProcessorArgCombinedWithQueueOption(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $this->consumer->expects(self::never())
            ->method('consume');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['--queue' => ['oro.default'], 'processor-service' => 'my_proc']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertEquals(
            "\n" .
            " [ERROR] The \"processor-service\" argument cannot be used together with the      \n" .
            "         \"--queue\" option. Specify the processor inside the --queue value using \n" .
            "         the \"processor\" key, e.g.                                              \n" .
            "         --queue=\"name=oro.default,processor=my_processor\".                     \n" .
            "\n",
            $tester->getDisplay()
        );
    }

    public function testExecuteReturnsFailureWhenDuplicateQueueOptionProvided(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $this->queueOptionValueParser->expects(self::exactly(2))
            ->method('parse')
            ->willReturnMap([
                ['oro.default', ['name' => 'oro.default', 'queueSettings' => [QueueConsumer::PROCESSOR => '']]],
            ]);

        $this->consumer->expects(self::never())
            ->method('consume');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['--queue' => ['oro.default', 'oro.default']]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertEquals(
            "\n" .
            " [ERROR] Duplicate --queue value: queue \"oro.default\" was specified more than   \n" .
            "         once.                                                                  \n" .
            "\n",
            $tester->getDisplay()
        );
    }

    public function testExecuteReturnsFailureWhenQueueOptionResolvesToEmptyName(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $this->queueOptionValueParser->expects(self::once())
            ->method('parse')
            ->willReturn(['name' => '', 'queueSettings' => []]);

        $this->consumer->expects(self::never())
            ->method('consume');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['--queue' => ['some-bad-value']]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertEquals(
            "\n" .
            " [ERROR] A --queue value resolved to an empty queue name. Original value:       \n" .
            "         \"some-bad-value\"                                                       \n" .
            "\n",
            $tester->getDisplay()
        );
    }

    public function testExecuteSuccessWithLongNotationPlainQueueName(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $this->queueOptionValueParser->expects(self::once())
            ->method('parse')
            ->with('oro.default')
            ->willReturn(['name' => 'oro.default', 'queueSettings' => [QueueConsumer::PROCESSOR => '']]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())->method('close');

        $this->consumer->expects(self::once())
            ->method('bindQueue')
            ->with('oro.default', [QueueConsumer::PROCESSOR => ''])
            ->willReturnSelf();
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['--queue' => ['oro.default']]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteSuccessWithLongNotationKeyValueFormat(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $this->queueOptionValueParser->expects(self::once())
            ->method('parse')
            ->with('name=oro.index,processor=my_proc')
            ->willReturn(['name' => 'oro.index', 'queueSettings' => [QueueConsumer::PROCESSOR => 'my_proc']]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())->method('close');

        $this->consumer->expects(self::once())
            ->method('bindQueue')
            ->with('oro.index', [QueueConsumer::PROCESSOR => 'my_proc'])
            ->willReturnSelf();
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['--queue' => ['name=oro.index,processor=my_proc']]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteSuccessWithShortNotationMultipleQueues(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('close');

        $callIndex = 0;
        $expectedArgs = [
            ['q1', [QueueConsumer::PROCESSOR => '']],
            ['q2', [QueueConsumer::PROCESSOR => '']],
        ];
        $this->consumer->expects(self::exactly(2))
            ->method('bindQueue')
            ->willReturnCallback(function () use (&$callIndex, $expectedArgs) {
                $args = func_get_args();
                self::assertSame($expectedArgs[$callIndex], $args);
                $callIndex++;

                return $this->consumer;
            });
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['queue' => 'q1,q2']);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testShouldExecuteConsumptionAndUseDefaultMode(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('close');

        $this->consumer->expects(self::once())
            ->method('bindQueue')
            ->with('queue-name', [QueueConsumer::PROCESSOR => 'processor-service'])
            ->willReturnSelf();
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);
        $this->consumer->expects(self::once())
            ->method('setConsumptionMode')
            ->with('default');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['queue' => 'queue-name', 'processor-service' => 'processor-service']);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testShouldReturnFailureAndDisplayErrorWhenUnsupportedModeProvided(): void
    {
        $this->consumer->expects(self::never())
            ->method('consume');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['queue' => 'queue-name', '--mode' => 'unsupported_mode']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame(
            "\n" .
            " [ERROR] No non-default consumption modes are registered in the system. Please  \n" .
            "         check your configuration.                                              \n" .
            "\n",
            $tester->getDisplay()
        );
    }
}
