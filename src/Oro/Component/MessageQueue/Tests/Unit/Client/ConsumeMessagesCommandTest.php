<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Bundle\MessageQueueBundle\Job\JobManager;
use Oro\Component\MessageQueue\Client\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
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
    private DestinationMetaRegistry&MockObject $registry;
    private QueueOptionValueParser&MockObject $queueOptionValueParser;
    private ConsumeMessagesCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->previousColumns = getenv('COLUMNS');
        putenv('COLUMNS=80');

        $this->consumer = $this->createMock(QueueConsumer::class);
        $this->registry = $this->createMock(DestinationMetaRegistry::class);
        $this->jobManager = $this->createMock(JobManager::class);

        $this->queueIteratorFactoryRegistry = $this->createMock(QueueIteratorFactoryRegistry::class);
        $this->queueIteratorFactoryRegistry
            ->expects(self::any())
            ->method('getConsumptionModes')
            ->willReturn([DefaultQueueIterator::NAME, StrictPriorityInterleavingQueueIterator::NAME]);

        $this->queueOptionValueParser = $this->createMock(QueueOptionValueParser::class);

        $this->command = new ConsumeMessagesCommand(
            $this->consumer,
            $this->registry,
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        putenv($this->previousColumns !== false ? 'COLUMNS=' . $this->previousColumns : 'COLUMNS');
    }

    public function testShouldHaveCommandName(): void
    {
        self::assertSame('oro:message-queue:consume', $this->command->getName());
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

    public function testShouldHaveExpectedArguments(): void
    {
        $arguments = $this->command->getDefinition()->getArguments();

        self::assertCount(1, $arguments);
        self::assertArrayHasKey('queue', $arguments);
    }

    public function testModeOptionDefaultIsDefault(): void
    {
        $modeOption = $this->command->getDefinition()->getOption('mode');

        self::assertSame(DefaultQueueIterator::NAME, $modeOption->getDefault());
    }

    public function testExecuteBindsAllDestinationsWhenNoQueueSpecified(): void
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

        $callIndex = 0;
        $expectedQueues = ['prefix.default', 'prefix.alternate'];
        $this->consumer->expects(self::exactly(2))
            ->method('bindQueue')
            ->willReturnCallback(function (string $queueName) use (&$callIndex, $expectedQueues) {
                self::assertSame($expectedQueues[$callIndex], $queueName);
                $callIndex++;

                return $this->consumer;
            });

        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->registry->expects(self::once())
            ->method('getDestinationsMeta')
            ->willReturn([
                new DestinationMeta('default', 'prefix.default'),
                new DestinationMeta('alternate', 'prefix.alternate'),
            ]);

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteWithShortNotationSingleQueue(): void
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
            ->with('prefix.custom');
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->registry->expects(self::once())
            ->method('getDestinationMeta')
            ->with('custom')
            ->willReturn(new DestinationMeta('custom', 'prefix.custom'));

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['queue' => 'custom']);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteWithShortNotationMultipleQueues(): void
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

        $callIndex = 0;
        $expectedQueues = ['prefix.default', 'prefix.alternate'];
        $this->consumer->expects(self::exactly(2))
            ->method('bindQueue')
            ->willReturnCallback(function (string $queueName) use (&$callIndex, $expectedQueues) {
                self::assertSame($expectedQueues[$callIndex], $queueName);
                $callIndex++;

                return $this->consumer;
            });

        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->registry->expects(self::exactly(2))
            ->method('getDestinationMeta')
            ->willReturnCallback(function (string $name) {
                $map = [
                    'default' => new DestinationMeta('default', 'prefix.default'),
                    'alternate' => new DestinationMeta('alternate', 'prefix.alternate'),
                ];

                return $map[$name];
            });

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['queue' => 'default,alternate']);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteWithLongNotationPlainQueueName(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $this->queueOptionValueParser->expects(self::once())
            ->method('parse')
            ->with('default')
            ->willReturn(['name' => 'default', 'queueSettings' => [QueueConsumer::PROCESSOR => '']]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('close');

        $this->consumer->expects(self::once())
            ->method('setConsumptionMode')
            ->with(DefaultQueueIterator::NAME);
        $this->consumer->expects(self::once())
            ->method('bindQueue')
            ->with('prefix.default', [QueueConsumer::PROCESSOR => '']);
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->registry->expects(self::once())
            ->method('getDestinationMeta')
            ->with('default')
            ->willReturn(new DestinationMeta('default', 'prefix.default'));

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['--queue' => ['default']]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteWithLongNotationKeyValueFormat(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $this->queueOptionValueParser->expects(self::once())
            ->method('parse')
            ->with('name=default,weight=3')
            ->willReturn(['name' => 'default', 'queueSettings' => [QueueConsumer::PROCESSOR => '', 'weight' => '3']]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('close');

        $this->consumer->expects(self::once())
            ->method('setConsumptionMode')
            ->with(DefaultQueueIterator::NAME);
        $this->consumer->expects(self::once())
            ->method('bindQueue')
            ->with('prefix.default', [QueueConsumer::PROCESSOR => '', 'weight' => '3']);
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->registry->expects(self::once())
            ->method('getDestinationMeta')
            ->with('default')
            ->willReturn(new DestinationMeta('default', 'prefix.default'));

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['--queue' => ['name=default,weight=3']]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteWithLongNotationMultipleQueues(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $this->queueOptionValueParser->expects(self::exactly(2))
            ->method('parse')
            ->willReturnCallback(function (string $value) {
                $results = [
                    'default' => ['name' => 'default', 'queueSettings' => [QueueConsumer::PROCESSOR => '']],
                    'alternate' => ['name' => 'alternate', 'queueSettings' => [QueueConsumer::PROCESSOR => '']],
                ];

                return $results[$value];
            });

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('close');

        $this->consumer->expects(self::once())
            ->method('setConsumptionMode')
            ->with(DefaultQueueIterator::NAME);

        $bindCallIndex = 0;
        $expectedBindCalls = [
            ['prefix.default', [QueueConsumer::PROCESSOR => '']],
            ['prefix.alternate', [QueueConsumer::PROCESSOR => '']],
        ];
        $this->consumer->expects(self::exactly(2))
            ->method('bindQueue')
            ->willReturnCallback(function () use (&$bindCallIndex, $expectedBindCalls) {
                $args = func_get_args();
                self::assertSame($expectedBindCalls[$bindCallIndex], $args);
                $bindCallIndex++;

                return $this->consumer;
            });

        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->registry->expects(self::exactly(2))
            ->method('getDestinationMeta')
            ->willReturnCallback(function (string $name) {
                $map = [
                    'default' => new DestinationMeta('default', 'prefix.default'),
                    'alternate' => new DestinationMeta('alternate', 'prefix.alternate'),
                ];

                return $map[$name];
            });

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['--queue' => ['default', 'alternate']]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteWithCustomMode(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('close');

        $this->consumer->expects(self::once())
            ->method('setConsumptionMode')
            ->with(StrictPriorityInterleavingQueueIterator::NAME);
        $this->consumer->expects(self::once())
            ->method('bindQueue')
            ->with('prefix.default');
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->registry->expects(self::once())
            ->method('getDestinationMeta')
            ->with('default')
            ->willReturn(new DestinationMeta('default', 'prefix.default'));

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([
            'queue' => 'default',
            '--mode' => StrictPriorityInterleavingQueueIterator::NAME,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteReturnsFailureForUnsupportedMode(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $this->consumer->expects(self::never())
            ->method('consume');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['queue' => 'default', '--mode' => 'noop']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertEquals(
            "\n" .
            " [ERROR] Unknown consumption mode \"noop\". Supported modes: default,             \n" .
            "         strict-priority-interleaving                                           \n" .
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
        $exitCode = $tester->execute(['queue' => 'default', '--queue' => ['alternate']]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertEquals(
            "\n" .
            " [ERROR] Cannot use both the \"queue\" positional argument and the \"--queue\"      \n" .
            "         option at the same time. Use one notation or the other.                \n" .
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
            ->with('bad-value')
            ->willReturn(['name' => '', 'queueSettings' => []]);

        $this->consumer->expects(self::never())
            ->method('consume');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['--queue' => ['bad-value']]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertEquals(
            "\n" .
            " [ERROR] A --queue value resolved to an empty client-level queue name. Original \n" .
            "         value: \"bad-value\"                                                     \n" .
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
                ['default', ['name' => 'default', 'queueSettings' => [QueueConsumer::PROCESSOR => '']]],
            ]);

        $this->consumer->expects(self::never())
            ->method('consume');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['--queue' => ['default', 'default']]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertEquals(
            "\n" .
            " [ERROR] Duplicate --queue value: client-level queue \"default\" was specified    \n" .
            "         more than once.                                                        \n" .
            "\n",
            $tester->getDisplay()
        );
    }

    public function testExecuteClosesConnectionEvenWhenConsumeThrowsException(): void
    {
        $this->command->setJobManager($this->jobManager);
        $this->command->setQueueIteratorFactoryRegistry($this->queueIteratorFactoryRegistry);
        $this->command->setQueueOptionValueParser($this->queueOptionValueParser);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('close');

        $this->consumer->expects(self::once())
            ->method('bindQueue')
            ->with('prefix.default');
        $this->consumer->expects(self::once())
            ->method('consume')
            ->willThrowException(new \RuntimeException('consume failed'));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->registry->expects(self::once())
            ->method('getDestinationsMeta')
            ->willReturn([new DestinationMeta('default', 'prefix.default')]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('consume failed');

        $tester = new CommandTester($this->command);
        $tester->execute([]);
    }

    public function testExecuteResolvesClientLevelQueueNameToTransportQueueName(): void
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
            ->with('oro.prefix.default');
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->registry->expects(self::once())
            ->method('getDestinationMeta')
            ->with('default')
            ->willReturn(new DestinationMeta('default', 'oro.prefix.default'));

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['queue' => 'default']);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testShouldExecuteConsumptionAndUseDefaultQueueName(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('close');

        $this->consumer->expects(self::once())
            ->method('bindQueue')
            ->with('aprefixt.adefaultqueuename', []);
        $this->consumer->expects(self::once())
            ->method('consume')
            ->with(self::isInstanceOf(ChainExtension::class));
        $this->consumer->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->registry->expects(self::once())
            ->method('getDestinationsMeta')
            ->willReturn([new DestinationMeta('aclient', 'aprefixt.adefaultqueuename')]);

        $tester = new CommandTester($this->command);
        $tester->execute([]);
    }

    public function testShouldReturnFailureAndDisplayErrorWhenUnsupportedModeProvided(): void
    {
        $this->consumer->expects(self::never())
            ->method('consume');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['--mode' => 'unsupported_mode']);

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
