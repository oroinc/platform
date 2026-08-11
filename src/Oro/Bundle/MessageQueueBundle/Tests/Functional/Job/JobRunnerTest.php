<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Job;

use Oro\Bundle\MessageQueueBundle\Test\Async\Topic\SampleChildJobTopic;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;

class JobRunnerTest extends WebTestCase
{
    use JobsAwareTestTrait;
    use MessageQueueExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        self::purgeMessageQueue();
    }

    #[\Override]
    protected function tearDown(): void
    {
        self::purgeMessageQueue();
    }

    public function testMessageWithFailedJobIsRejected(): void
    {
        $childJob = $this->createDelayedJob();
        $this->getJobProcessor()->failChildJob($childJob);

        $message = self::sendMessage(SampleChildJobTopic::getName(), ['jobId' => $childJob->getId()]);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $message);
        self::assertTrue(
            self::getLoggerTestHandler()->hasWarningThatContains(
                sprintf(
                    'Job "%s" cannot be started because it is already in status "%s"',
                    $childJob->getId(),
                    Job::STATUS_FAILED
                )
            )
        );
        self::assertFalse(self::getLoggerTestHandler()->hasErrorRecords());
    }
}
