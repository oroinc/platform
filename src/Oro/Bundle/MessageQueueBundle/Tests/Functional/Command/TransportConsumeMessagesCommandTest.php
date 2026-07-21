<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Command;

use Oro\Bundle\MessageQueueBundle\Command\TransportConsumeMessagesCommand;
use Oro\Bundle\MessageQueueBundle\Event\TransportConsumeMessagesCommandConsoleEvent;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityHighTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityMediumTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\Meta\TopicMetaRegistry;
use Oro\Component\MessageQueue\Consumption\QueueIterator\DefaultQueueIterator;
use Oro\Component\MessageQueue\Consumption\QueueIterator\QueueIteratorFactoryRegistry;
use Oro\Component\MessageQueue\Log\ConsumerState;

final class TransportConsumeMessagesCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    private const string COMMAND = 'oro:message-queue:transport:consume';

    private const string QUEUE_HIGH = 'oro.test.priority.high';
    private const string QUEUE_MEDIUM = 'oro.test.priority.medium';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        self::purgeMessageQueue(self::QUEUE_HIGH);
        self::purgeMessageQueue(self::QUEUE_MEDIUM);

        self::clearProcessedMessages();

        self::getConsumer()->unbindQueues();
    }

    #[\Override]
    protected function tearDown(): void
    {
        self::purgeMessageQueue(self::QUEUE_HIGH);
        self::purgeMessageQueue(self::QUEUE_MEDIUM);

        self::clearProcessedMessages();

        self::getConsumer()->unbindQueues();
    }

    // =========================================================================
    // Section A — Command metadata
    // =========================================================================

    public function testCommandIsRegisteredInContainer(): void
    {
        $command = self::getContainer()->get(TransportConsumeMessagesCommand::class);

        self::assertInstanceOf(TransportConsumeMessagesCommand::class, $command);
        self::assertSame('oro:message-queue:transport:consume', $command->getName());

        $definition = $command->getDefinition();

        self::assertTrue($definition->hasArgument('queue'), 'Command must define the "queue" argument.');
        self::assertTrue(
            $definition->hasArgument('processor-service'),
            'Command must define the "processor-service" argument.'
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
        /** @var TransportConsumeMessagesCommand $command */
        $command = self::getContainer()->get(TransportConsumeMessagesCommand::class);
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
            self::QUEUE_HIGH,
            '--queue=' . self::QUEUE_HIGH,
        ]);

        self::assertStringContainsString(
            '[ERROR] Cannot use both the "queue" positional argument and the "--queue" option at the same time.'
            . ' Use one notation or the other.',
            $output
        );
    }

    public function testFailsWhenNoQueueIsProvided(): void
    {
        $output = self::runCommand(self::COMMAND, []);

        self::assertStringContainsString(
            '[ERROR] You must provide queue names either via the "queue" argument (short notation)'
            . ' or via the "--queue" option (long notation).',
            $output
        );
    }

    public function testFailsWhenProcessorServiceArgumentCombinedWithQueueOption(): void
    {
        // Pass empty string for the "queue" positional arg so hasQueueArg=false,
        // then pass a processor-service value as the second positional arg,
        // plus a --queue option. This targets the specific validation branch.
        $output = self::runCommand(self::COMMAND, [
            '',
            'some.processor',
            '--queue=' . self::QUEUE_HIGH,
        ]);

        self::assertStringContainsString(
            '[ERROR] The "processor-service" argument cannot be used together with the "--queue" option.'
            . ' Specify the processor inside the --queue value using the "processor" key,'
            . ' e.g. --queue="name=oro.default,processor=my_processor".',
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
            self::QUEUE_HIGH,
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
            '[ERROR] A --queue value resolved to an empty queue name. Original value: "name="',
            $output
        );
    }

    public function testFailsWhenQueueOptionIsDuplicated(): void
    {
        $output = self::runCommand(self::COMMAND, [
            '--queue=' . self::QUEUE_HIGH,
            '--queue=' . self::QUEUE_HIGH,
        ]);

        self::assertStringContainsString(
            '[ERROR] Duplicate --queue value: queue "' . self::QUEUE_HIGH
            . '" was specified more than once.',
            $output
        );
    }

    // =========================================================================
    // Section C — initialize() event dispatch
    // =========================================================================

    public function testInitializeDispatchesConsoleEventWithCommandInstance(): void
    {
        $invoked = false;
        $capturedEvent = null;

        $listener = static function (TransportConsumeMessagesCommandConsoleEvent $event) use (
            &$invoked,
            &$capturedEvent
        ): void {
            $invoked = true;
            $capturedEvent = $event;
        };

        $eventDispatcher = self::getContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(TransportConsumeMessagesCommandConsoleEvent::class, $listener);

        try {
            self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'event-test']);

            self::runCommand(self::COMMAND, [
                self::QUEUE_HIGH,
                '--message-limit=1',
            ]);

            self::assertTrue($invoked, 'The TransportConsumeMessagesCommandConsoleEvent listener was not invoked.');
            self::assertInstanceOf(TransportConsumeMessagesCommandConsoleEvent::class, $capturedEvent);
            self::assertInstanceOf(TransportConsumeMessagesCommand::class, $capturedEvent->getCommand());
        } finally {
            $eventDispatcher->removeListener(TransportConsumeMessagesCommandConsoleEvent::class, $listener);
        }
    }

    // =========================================================================
    // Section D — LimitsExtensionsCommandTrait options
    // =========================================================================

    public function testInvalidTimeLimitOptionOutputsError(): void
    {
        $output = self::runCommand(self::COMMAND, [
            self::QUEUE_HIGH,
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
            self::QUEUE_HIGH,
            '--message-limit=2',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(2, self::getProcessedMessages());
    }

    // =========================================================================
    // Section E — Happy-path consumption
    // =========================================================================

    public function testShortNotationSingleQueueConsumesOneMessage(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            self::QUEUE_HIGH,
            '--message-limit=1',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(1, self::getProcessedMessages());
    }

    public function testShortNotationMultipleQueuesCommaDelimited(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'high1']);
        self::getMessageProducer()->send(PriorityMediumTestTopic::NAME, ['label' => 'med1']);

        $output = self::runCommand(self::COMMAND, [
            self::QUEUE_HIGH . ',' . self::QUEUE_MEDIUM,
            '--message-limit=2',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(2, self::getProcessedMessages());
    }

    public function testLongNotationSingleQueueOptionConsumesOneMessage(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            '--queue=' . self::QUEUE_HIGH,
            '--message-limit=1',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(1, self::getProcessedMessages());
    }

    public function testLongNotationKeyValueWithNameKeyConsumesMessage(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            '--queue=name=' . self::QUEUE_HIGH,
            '--message-limit=1',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(1, self::getProcessedMessages());
    }

    public function testLongNotationKeyValueWithProcessorKeyConsumesMessage(): void
    {
        /** @var TopicMetaRegistry $topicMetaRegistry */
        $topicMetaRegistry = self::getContainer()->get('oro_message_queue.client.meta.topic_meta_registry');
        $processorIds = $topicMetaRegistry
            ->getTopicMeta(PriorityHighTestTopic::NAME)
            ->getAllMessageProcessors();

        self::assertNotEmpty($processorIds, 'No processor is registered for PriorityHighTestTopic.');

        $processorServiceId = $processorIds[0];

        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            '--queue=name=' . self::QUEUE_HIGH . ',processor=' . $processorServiceId,
            '--message-limit=1',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(1, self::getProcessedMessages());
        self::assertProcessedMessageProcessor(
            $processorServiceId,
            self::getProcessedMessages()[0]['message']
        );
    }

    public function testProcessorServiceArgumentInShortNotation(): void
    {
        /** @var TopicMetaRegistry $topicMetaRegistry */
        $topicMetaRegistry = self::getContainer()->get('oro_message_queue.client.meta.topic_meta_registry');
        $processorIds = $topicMetaRegistry
            ->getTopicMeta(PriorityHighTestTopic::NAME)
            ->getAllMessageProcessors();

        self::assertNotEmpty($processorIds, 'No processor is registered for PriorityHighTestTopic.');

        $processorServiceId = $processorIds[0];

        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            self::QUEUE_HIGH,
            $processorServiceId,
            '--message-limit=1',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(1, self::getProcessedMessages());
        self::assertProcessedMessageProcessor(
            $processorServiceId,
            self::getProcessedMessages()[0]['message']
        );
    }

    public function testExplicitDefaultModeOptionSucceeds(): void
    {
        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            self::QUEUE_HIGH,
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
            self::QUEUE_HIGH,
            '--mode=' . $nonDefaultModes[0],
            '--message-limit=1',
        ]);

        self::assertStringNotContainsString('[ERROR]', $output);
        self::assertCount(1, self::getProcessedMessages());
    }

    // =========================================================================
    // Section F — ConsumerState lifecycle
    // =========================================================================

    public function testConsumerStateIsInactiveAfterSuccessfulConsumption(): void
    {
        /** @var ConsumerState $consumerState */
        $consumerState = self::getContainer()->get('oro_message_queue.log.consumer_state');

        self::getMessageProducer()->send(PriorityHighTestTopic::NAME, ['label' => 'msg1']);

        $output = self::runCommand(self::COMMAND, [
            self::QUEUE_HIGH,
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

        $output = self::runCommand(self::COMMAND, []);

        self::assertStringContainsString(
            '[ERROR] You must provide queue names either via the "queue" argument (short notation)'
            . ' or via the "--queue" option (long notation).',
            $output
        );
        self::assertFalse(
            $consumerState->isConsumptionStarted(),
            'ConsumerState must not be started when input validation fails.'
        );
    }
}
