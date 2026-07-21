<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Command;

use Oro\Bundle\MessageQueueBundle\Command\ClientConsumeMessagesCommand;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityHighTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityLowTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityMediumTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\QueueIterator\DefaultQueueIterator;
use Oro\Component\MessageQueue\Consumption\QueueIterator\QueueIteratorFactoryRegistry;
use Oro\Component\MessageQueue\Log\ConsumerState;

final class ClientConsumeMessagesCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    private const string COMMAND = 'oro:message-queue:consume';

    private const string CLIENT_QUEUE_HIGH = 'test.priority.high';
    private const string CLIENT_QUEUE_MEDIUM = 'test.priority.medium';
    private const string CLIENT_QUEUE_LOW = 'test.priority.low';

    private const string TRANSPORT_QUEUE_HIGH = 'oro.test.priority.high';
    private const string TRANSPORT_QUEUE_MEDIUM = 'oro.test.priority.medium';
    private const string TRANSPORT_QUEUE_LOW = 'oro.test.priority.low';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        self::purgeMessageQueue(self::TRANSPORT_QUEUE_HIGH);
        self::purgeMessageQueue(self::TRANSPORT_QUEUE_MEDIUM);
        self::purgeMessageQueue(self::TRANSPORT_QUEUE_LOW);

        self::clearProcessedMessages();

        self::getConsumer()->unbindQueues();
    }

    #[\Override]
    protected function tearDown(): void
    {
        self::purgeMessageQueue(self::TRANSPORT_QUEUE_HIGH);
        self::purgeMessageQueue(self::TRANSPORT_QUEUE_MEDIUM);
        self::purgeMessageQueue(self::TRANSPORT_QUEUE_LOW);

        self::clearProcessedMessages();

        self::getConsumer()->unbindQueues();
    }

    // =========================================================================
    // Section A — Command metadata
    // =========================================================================

    public function testCommandIsRegisteredWithCorrectNameAndOptions(): void
    {
        $command = self::getContainer()->get(ClientConsumeMessagesCommand::class);

        self::assertInstanceOf(ClientConsumeMessagesCommand::class, $command);
        self::assertSame('oro:message-queue:consume', $command->getName());

        $definition = $command->getDefinition();

        self::assertTrue(
            $definition->hasArgument('queue'),
            'Command must define the "queue" argument.'
        );
        self::assertFalse(
            $definition->hasArgument('processor-service'),
            'Command must NOT define the "processor-service" argument.'
        );

        self::assertTrue($definition->hasOption('queue'), 'Command must define the "--queue" option.');
        self::assertTrue($definition->hasOption('mode'), 'Command must define the "--mode" option.');
        self::assertTrue($definition->hasOption('message-limit'), 'Command must define the "--message-limit" option.');
        self::assertTrue($definition->hasOption('time-limit'), 'Command must define the "--time-limit" option.');
        self::assertTrue($definition->hasOption('memory-limit'), 'Command must define the "--memory-limit" option.');
        self::assertTrue($definition->hasOption('object-limit'), 'Command must define the "--object-limit" option.');
        self::assertTrue($definition->hasOption('gc-limit'), 'Command must define the "--gc-limit" option.');
        self::assertTrue(
            $definition->hasOption('stop-when-unique-jobs-processed'),
            'Command must define the "--stop-when-unique-jobs-processed" option.'
        );
    }

    public function testHelpDocumentsConsumptionModes(): void
    {
        /** @var ClientConsumeMessagesCommand $command */
        $command = self::getContainer()->get(ClientConsumeMessagesCommand::class);
        $help = $command->getHelp();

        self::assertStringContainsString('<comment>Consumption modes</comment>', $help);

        foreach (
            [
                [
                    'Q1(1), Q2(1), Q3(1), Q1(1), Q2(1), Q3(1)',
                    'Help must document default mode repeating-slot schema.',
                ],
                [
                    'Q1(*), Q2(*), Q3(*)',
                    'Help must document sequential-exhaustive three-queue schema.',
                ],
                [
                    'Q1(*), Q2(1), Q1(*), Q3(1)',
                    'Help must document strict-priority-interleaving interleave schema.',
                ],
                [
                    '( Q1(*), Q2(1) )(*), Q3(1)',
                    'Help must document hierarchical-strict-priority nested-block schema.',
                ],
                [
                    'Q1(w1), Q2(w2), Q3(w3), Q1(w1), Q2(w2), Q3(w3)',
                    'Help must document weighted-round-robin three-queue cycle.',
                ],
            ] as [$schemaSnippet, $message]
        ) {
            self::assertStringContainsString($schemaSnippet, $help, $message);
        }

        foreach (
            [
                'default',
                'sequential-exhaustive',
                'strict-priority-interleaving',
                'hierarchical-strict-priority-interleaving',
                'weighted-round-robin',
            ] as $modeName
        ) {
            self::assertStringContainsString(
                '<info>' . $modeName . '</info>',
                $help,
                sprintf('Help text must summarize mode "%s".', $modeName)
            );
        }
    }

    // =========================================================================
    // Section B — Input validation (no messages needed)
    // =========================================================================

    public function testFailsWhenBothQueueArgumentAndQueueOptionAreProvided(): void
    {
        $output = self::runCommand(self::COMMAND, [
            self::CLIENT_QUEUE_HIGH,
            '--queue=' . self::CLIENT_QUEUE_HIGH,
        ]);

        self::assertStringContainsString(
            '[ERROR] Cannot use both the "queue" positional argument and the "--queue" option at the same time.'
            . ' Use one notation or the other.',
            $output
        );
    }

    public function testFailsWhenUnknownConsumptionModeIsGiven(): void
    {
        /** @var QueueIteratorFactoryRegistry $factoryRegistry */
        $factoryRegistry = self::getContainer()->get(
            'oro_message_queue.consumption.queue_iterator.factory_registry'
        );
        $supportedModes = implode(', ', $factoryRegistry->getConsumptionModes());

        $output = self::runCommand(self::COMMAND, [
            self::CLIENT_QUEUE_HIGH,
            '--mode=no-such-mode',
        ]);

        self::assertStringContainsString(
            '[ERROR] Unknown consumption mode "no-such-mode". Supported modes: ' . $supportedModes,
            $output
        );
    }

    public function testFailsWhenQueueOptionResolvesToEmptyName(): void
    {
        $output = self::runCommand(self::COMMAND, [
            '--queue=name=',
        ]);

        self::assertStringContainsString(
            '[ERROR] A --queue value resolved to an empty client-level queue name. Original value: "name="',
            $output
        );
    }

    public function testFailsWhenQueueOptionIsDuplicated(): void
    {
        $output = self::runCommand(self::COMMAND, [
            '--queue=' . self::CLIENT_QUEUE_HIGH,
            '--queue=' . self::CLIENT_QUEUE_HIGH,
        ]);

        self::assertStringContainsString(
            '[ERROR] Duplicate --queue value: client-level queue "' . self::CLIENT_QUEUE_HIGH
            . '" was specified more than once.',
            $output
        );
    }

    public function testFailsWhenShortNotationQueueArgumentIsBlank(): void
    {
        $output = self::runCommand(self::COMMAND, [
            '   ',
        ]);

        self::assertStringContainsString(
            '[ERROR] The "queue" argument must contain at least one client-level queue name'
            . ' when used in short notation.',
            $output
        );
    }

    // =========================================================================
    // Section C — LimitsExtensionsCommandTrait options
    // =========================================================================

    public function testInvalidTimeLimitOptionOutputsError(): void
    {
        $output = self::runCommand(self::COMMAND, [
            self::CLIENT_QUEUE_HIGH,
            '--time-limit=not-a-date',
        ]);

        self::assertStringContainsString('Invalid time limit', $output);
    }

    public function testMessageLimitOptionTerminatesConsumptionAfterNMessages(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg2']);
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg3']);

        $output = self::runCommand(self::COMMAND, [
            self::CLIENT_QUEUE_HIGH,
            '--message-limit=2',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(2, self::getProcessedMessages());
    }

    // =========================================================================
    // Section D — Default behavior when no queue is specified
    // =========================================================================

    public function testNoQueueSpecifiedConsumesAllRegisteredDestinations(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'high-msg']);
        self::getMessageProducer()->send(PriorityMediumTestTopic::NAME, ['label' => 'medium-msg']);
        self::getMessageProducer()->send(PriorityLowTestTopic::NAME, ['label' => 'low-msg']);

        $output = self::runCommand(self::COMMAND, [
            '--message-limit=3',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);

        $processedMessages = self::getProcessedMessages();
        self::assertCount(3, $processedMessages);

        $queueNames = array_map(
            static fn (array $processedMessage) => $processedMessage['context']->getQueueName(),
            $processedMessages
        );

        self::assertContains(self::TRANSPORT_QUEUE_HIGH, $queueNames);
        self::assertContains(self::TRANSPORT_QUEUE_MEDIUM, $queueNames);
        self::assertContains(self::TRANSPORT_QUEUE_LOW, $queueNames);
    }

    // =========================================================================
    // Section E — Happy-path short notation
    // =========================================================================

    public function testShortNotationSingleClientQueueConsumesMessage(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            self::CLIENT_QUEUE_HIGH,
            '--message-limit=1',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(1, self::getProcessedMessages());

        $processedMessages = self::getProcessedMessages();
        self::assertSame(
            self::TRANSPORT_QUEUE_HIGH,
            $processedMessages[0]['context']->getQueueName()
        );
    }

    public function testShortNotationMultipleClientQueuesCommaDelimited(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'high1']);
        self::getMessageProducer()->send(PriorityMediumTestTopic::NAME, ['label' => 'med1']);

        $output = self::runCommand(self::COMMAND, [
            self::CLIENT_QUEUE_HIGH . ',' . self::CLIENT_QUEUE_MEDIUM,
            '--message-limit=2',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(2, self::getProcessedMessages());
    }

    // =========================================================================
    // Section F — Happy-path long notation
    // =========================================================================

    public function testLongNotationSingleQueueOptionConsumesMessage(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            '--queue=' . self::CLIENT_QUEUE_HIGH,
            '--message-limit=1',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(1, self::getProcessedMessages());
    }

    public function testLongNotationKeyValueWithNameKeyConsumesMessage(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            '--queue=name=' . self::CLIENT_QUEUE_HIGH,
            '--message-limit=1',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(1, self::getProcessedMessages());
    }

    public function testLongNotationKeyValueWithExtraSettingsConsumesMessage(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            '--queue=name=' . self::CLIENT_QUEUE_HIGH . ',weight=3',
            '--message-limit=1',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(1, self::getProcessedMessages());
    }

    public function testLongNotationMultipleQueueOptionsConsumeMessages(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'high1']);
        self::getMessageProducer()->send(PriorityMediumTestTopic::NAME, ['label' => 'med1']);

        $output = self::runCommand(self::COMMAND, [
            '--queue=' . self::CLIENT_QUEUE_HIGH,
            '--queue=' . self::CLIENT_QUEUE_MEDIUM,
            '--message-limit=2',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(2, self::getProcessedMessages());
    }

    // =========================================================================
    // Section I — Consumption mode (--mode), analogous to transport consume command
    // =========================================================================

    public function testExplicitDefaultModeOptionSucceeds(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            self::CLIENT_QUEUE_HIGH,
            '--mode=' . DefaultQueueIterator::NAME,
            '--message-limit=1',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(1, self::getProcessedMessages());
    }

    public function testNonDefaultValidModeOptionSucceeds(): void
    {
        /** @var QueueIteratorFactoryRegistry $factoryRegistry */
        $factoryRegistry = self::getContainer()->get(
            'oro_message_queue.consumption.queue_iterator.factory_registry'
        );

        $nonDefaultModes = array_values(
            array_diff($factoryRegistry->getConsumptionModes(), [DefaultQueueIterator::NAME])
        );

        if (empty($nonDefaultModes)) {
            self::markTestSkipped('No alternative consumption mode is registered besides "default".');
        }

        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            self::CLIENT_QUEUE_HIGH,
            '--mode=' . $nonDefaultModes[0],
            '--message-limit=1',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(1, self::getProcessedMessages());
    }

    // =========================================================================
    // Section G — Client-to-transport name mapping
    // =========================================================================

    public function testClientQueueNameIsMappedToCorrectTransportQueueName(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            self::CLIENT_QUEUE_HIGH,
            '--message-limit=1',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);

        $processedMessages = self::getProcessedMessages();
        self::assertCount(1, $processedMessages);
        self::assertSame(
            self::TRANSPORT_QUEUE_HIGH,
            $processedMessages[0]['context']->getQueueName(),
            'The client-level queue name must be mapped to the transport-level queue name.'
        );
    }

    // =========================================================================
    // Section H — ConsumerState lifecycle
    // =========================================================================

    public function testConsumerStateIsInactiveAfterSuccessfulConsumption(): void
    {
        /** @var ConsumerState $consumerState */
        $consumerState = self::getContainer()->get('oro_message_queue.log.consumer_state');

        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            self::CLIENT_QUEUE_HIGH,
            '--message-limit=1',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertFalse(
            $consumerState->isConsumptionStarted(),
            'ConsumerState must be inactive after successful consumption.'
        );
    }

    public function testConsumerStateIsNeverStartedOnValidationFailure(): void
    {
        /** @var ConsumerState $consumerState */
        $consumerState = self::getContainer()->get('oro_message_queue.log.consumer_state');

        /** @var QueueIteratorFactoryRegistry $factoryRegistry */
        $factoryRegistry = self::getContainer()->get(
            'oro_message_queue.consumption.queue_iterator.factory_registry'
        );
        $supportedModes = implode(', ', $factoryRegistry->getConsumptionModes());

        $output = self::runCommand(self::COMMAND, [
            '--mode=invalid-mode',
        ]);

        self::assertStringContainsString(
            '[ERROR] Unknown consumption mode "invalid-mode". Supported modes: ' . $supportedModes,
            $output
        );
        self::assertFalse(
            $consumerState->isConsumptionStarted(),
            'ConsumerState must not be started when input validation fails.'
        );
    }
}
