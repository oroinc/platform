<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Job;

use Oro\Bundle\MessageQueueBundle\Test\Async\Topic\SampleChildJobTopic;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Exception\JobCannotBeStartedException;
use Oro\Component\MessageQueue\Job\Job;

class JobRunnerTest extends WebTestCase
{
    use JobsAwareTestTrait;
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

    public function testMessageWithFailedJobIsRejected(): void
    {
        $childJob = $this->createDelayedJob();
        $this->getJobProcessor()->failChildJob($childJob);

        self::sendMessage(SampleChildJobTopic::getName(), ['jobId' => $childJob->getId()]);

        $this->expectException(JobCannotBeStartedException::class);
        $this->expectErrorMessage(
            sprintf(
                'Job "%s" cannot be started because it is already in status "%s"',
                $childJob->getId(),
                Job::STATUS_FAILED
            )
        );

        self::consume();
    }
}
