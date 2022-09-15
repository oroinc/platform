<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Test\Async\Topic\SampleNormalizableBodyTopic;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\MessageQueueBundle\Test\Model\StdModel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

class RedeliveryMessageExtensionTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();

        self::purgeMessageQueue();
    }

    protected function tearDown(): void
    {
        self::purgeMessageQueue();
    }

    public function testMessageConsumptionIsInterruptedByMessageLimit(): void
    {
        $sentMessage = self::sendMessage(SampleNormalizableBodyTopic::getName(), ['entity' => ['SampleClass', 42]]);

        // 1 (first message) + 1 (rejected message) + 1 (requeued message) = 3
        self::consume(3, 100);

        self::assertFalse(self::getLoggerTestHandler()->hasErrorRecords());

        $processedMessages = self::getProcessedMessages();

        $resolvedBody = ['entity' => new StdModel(['SampleClass', 42])];

        $processedMessage1 = array_shift($processedMessages);
        self::assertEquals($sentMessage->getMessageId(), $processedMessage1['message']->getMessageId());
        self::assertEquals($resolvedBody, $processedMessage1['message']->getBody());
        self::assertEquals(MessageProcessorInterface::REQUEUE, $processedMessage1['context']->getStatus());

        $processedMessage2 = array_shift($processedMessages);
        self::assertEquals(MessageProcessorInterface::REJECT, $processedMessage2['context']->getStatus());

        $processedMessage3 = array_shift($processedMessages);
        self::assertEquals($resolvedBody, $processedMessage3['message']->getBody());
        self::assertEquals(MessageProcessorInterface::ACK, $processedMessage3['context']->getStatus());
    }
}
