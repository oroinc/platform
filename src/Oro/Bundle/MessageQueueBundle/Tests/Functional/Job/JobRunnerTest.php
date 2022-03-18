<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Job;

use Oro\Bundle\MessageQueueBundle\Test\Async\RedeliveryAwareMessageProcessor;
use Oro\Bundle\MessageQueueBundle\Test\Async\Topic\SampleChildJobTopic;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Exception\JobCannotBeStartedException;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class JobRunnerTest extends WebTestCase
{
    use JobsAwareTestTrait;

    private MessageProducerInterface $messageProducer;

    private QueueConsumer $consumer;

    protected function setUp(): void
    {
        $this->initClient();
        $container = self::getContainer();
        $this->messageProducer = $container->get('oro_message_queue.message_producer');
        $this->consumer = $container->get('oro_message_queue.consumption.queue_consumer');

        $this->clearMessages();
        RedeliveryAwareMessageProcessor::clearProcessedMessages();
    }

    protected function tearDown(): void
    {
        $this->clearMessages();
        RedeliveryAwareMessageProcessor::clearProcessedMessages();
    }

    public function testMessageWithFailedJobIsRejected(): void
    {
        $childJob = $this->createDelayedJob();
        $this->getJobProcessor()->failChildJob($childJob);

        $this->messageProducer->send(SampleChildJobTopic::getName(), ['jobId' => $childJob->getId()]);

        $this->expectException(JobCannotBeStartedException::class);
        $this->expectErrorMessage(
            sprintf(
                'Job "%s" cannot be started because it is already in status "%s"',
                $childJob->getId(),
                Job::STATUS_FAILED
            )
        );

        $this->consumer->bind('oro.default');
        $this->consumer->consume(
            new ChainExtension([
                new LimitConsumedMessagesExtension(1)
            ])
        );
    }

    private function clearMessages(): void
    {
        $connection = self::getContainer()->get(
            'oro_message_queue.transport.dbal.connection',
            ContainerInterface::NULL_ON_INVALID_REFERENCE
        );
        if ($connection instanceof DbalConnection) {
            $connection->getDBALConnection()->executeQuery('DELETE FROM ' . $connection->getTableName());
        }
    }
}
