<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobMiddleware;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Tests\Unit\Stub\JobAwareTopicInterfaceStub;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Oro\Component\MessageQueue\Topic\TopicRegistry;
use Oro\Component\Testing\Unit\EntityTrait;

class JobMiddlewareTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var JobRunner */
    private $jobRunner;

    /** @var TopicRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $topicRegistry;
    private JobMiddleware $middleware;

    private JobProcessor $jobProcessor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->topicRegistry = $this->createMock(TopicRegistry::class);
        $this->jobProcessor = $this->createMock(JobProcessor::class);

        $this->middleware = new JobMiddleware(
            $this->jobRunner,
            $this->topicRegistry,
            $this->jobProcessor
        );
    }

    public function testHandle(): void
    {
        $jobName = 'job_name';
        $topicName = 'topic_name';
        $messageId = 'id1';
        $body = [];

        $topic = $this->createMock(JobAwareTopicInterfaceStub::class);
        $job = $this->createMock(Job::class);
        $message = new Message();
        $message->setProperties([
            Config::PARAMETER_TOPIC_NAME => $topicName,
            JobAwareTopicInterface::UNIQUE_JOB_NAME => $jobName
        ]);
        $message->setBody($body);
        $message->setMessageId($messageId);

        $this->topicRegistry->expects(self::once())
            ->method('getJobAware')
            ->with($topicName)
            ->willReturn($topic);

        $topic->expects(self::once())
            ->method('createJobName')
            ->with($body)
            ->willReturn($jobName);

        $this->jobRunner->expects(self::once())
            ->method('createUnique')
            ->with($messageId, $jobName)
            ->willReturn($job);

        $this->middleware->handle($message);
    }
}
