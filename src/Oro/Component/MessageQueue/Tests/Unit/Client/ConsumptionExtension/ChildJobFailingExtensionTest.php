<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Client\ConsumptionExtension\ChildJobFailingExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

class ChildJobFailingExtensionTest extends \PHPUnit\Framework\TestCase
{
    public const JOB_ID = 'jobId';
    public const IGNORED_STATUSES = [Job::STATUS_FAILED, Job::STATUS_CANCELLED, Job::STATUS_STALE];

    private JobProcessor|\PHPUnit\Framework\MockObject\MockObject $jobProcessor;

    private LoggerInterface $logger;

    private ChildJobFailingExtension $extension;

    private Context $context;

    protected function setUp(): void
    {
        $this->jobProcessor = $this->createMock(JobProcessor::class);
        $this->logger = new TestLogger();

        $this->extension = new ChildJobFailingExtension($this->jobProcessor);

        $this->context = new Context($this->createMock(SessionInterface::class));
        $this->context->setLogger($this->logger);
    }

    public function testOnPostReceivedIsSkippedWhenNoMessage(): void
    {
        $this->jobProcessor
            ->expects(self::never())
            ->method(self::anything());

        $this->extension->onPostReceived($this->context);

        self::assertTrue($this->logger->hasInfo('Message is missing in context, skipping extension'));
    }

    /**
     * @dataProvider onPostReceivedIsSkippedWhenNoJobIdDataProvider
     */
    public function testOnPostReceivedIsSkippedWhenNoJobId(array $messageBody): void
    {
        $message = new Message();
        $message->setBody($messageBody);
        $this->context->setMessage($message);

        $this->jobProcessor
            ->expects(self::never())
            ->method(self::anything());

        $this->extension->onPostReceived($this->context);
    }

    public function onPostReceivedIsSkippedWhenNoJobIdDataProvider(): array
    {
        return [
            [[]],
            [[ChildJobFailingExtension::JOB_ID => null]],
            [[ChildJobFailingExtension::JOB_ID => true]],
            [[ChildJobFailingExtension::JOB_ID => false]],
            [[ChildJobFailingExtension::JOB_ID => '']],
            [[ChildJobFailingExtension::JOB_ID => 0]],
            [[ChildJobFailingExtension::JOB_ID => -1]],
        ];
    }

    public function testOnPostReceivedIsSkippedWhenNoJob(): void
    {
        $message = new Message();
        $message->setMessageId('sample-id');
        $jobId = 42;
        $message->setBody([ChildJobFailingExtension::JOB_ID => $jobId]);
        $this->context->setMessage($message);
        $this->context->setStatus(MessageProcessorInterface::REJECT);

        $this->jobProcessor
            ->expects(self::once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn(null);

        $this->jobProcessor
            ->expects(self::never())
            ->method('failChildJob');

        $this->extension->onPostReceived($this->context);

        self::assertTrue(
            $this->logger->hasInfo('Child job #{jobId} is not found for the rejected message #{messageId}')
        );
    }

    public function testOnPostReceivedIsSkippedWhenRedelivered(): void
    {
        $message = new Message();
        $message->setMessageId('sample-id');
        $message->setRedelivered(true);
        $this->context->setMessage($message);
        $this->context->setStatus(MessageProcessorInterface::REJECT);

        $this->jobProcessor
            ->expects(self::never())
            ->method(self::anything());

        $this->extension->onPostReceived($this->context);
    }

    public function testOnPostReceivedIsSkippedWhenJobIsRoot(): void
    {
        $message = new Message();
        $message->setMessageId('sample-id');
        $jobId = 42;
        $message->setBody([ChildJobFailingExtension::JOB_ID => $jobId]);
        $this->context->setMessage($message);
        $this->context->setStatus(MessageProcessorInterface::REJECT);

        $job = new Job();
        $this->jobProcessor
            ->expects(self::once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($job);

        $this->jobProcessor
            ->expects(self::never())
            ->method('failChildJob');

        $this->extension->onPostReceived($this->context);
    }

    /**
     * @dataProvider ignoredStatusDataProvider
     */
    public function testOnPostReceivedIsSkippedWhenJobInIgnoredStatus(string $status): void
    {
        $message = new Message();
        $message->setMessageId('sample-id');
        $jobId = 42;
        $message->setBody([ChildJobFailingExtension::JOB_ID => $jobId]);
        $this->context->setMessage($message);
        $this->context->setStatus(MessageProcessorInterface::REJECT);

        $job = new Job();
        $job->setRootJob(new Job());
        $job->setStatus($status);
        $this->jobProcessor
            ->expects(self::once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($job);

        $this->jobProcessor
            ->expects(self::never())
            ->method('failChildJob');

        $this->extension->onPostReceived($this->context);
    }

    public function ignoredStatusDataProvider(): array
    {
        $data = [];
        foreach (ChildJobFailingExtension::IGNORED_JOB_STATUSES as $status) {
            $data[] = ['status' => $status];
        }

        return $data;
    }

    public function testOnPostReceivedShouldFailJob(): void
    {
        $message = new Message();
        $message->setMessageId('sample-id');
        $jobId = 42;
        $message->setBody([ChildJobFailingExtension::JOB_ID => $jobId]);
        $this->context->setMessage($message);
        $this->context->setStatus(MessageProcessorInterface::REJECT);

        $job = new Job();
        $job->setRootJob(new Job());
        $this->jobProcessor
            ->expects(self::once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($job);

        $this->jobProcessor
            ->expects(self::once())
            ->method('failChildJob')
            ->with($job);

        $this->extension->onPostReceived($this->context);

        self::assertTrue(
            $this->logger->hasInfo(
                'Child job #{jobId} status is set to "{status}" for the rejected message #"{messageId}"'
            )
        );
    }
}
