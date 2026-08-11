<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobExtension;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JobExtensionTest extends TestCase
{
    private JobRunner&MockObject $jobRunner;
    private JobExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->extension = new JobExtension($this->jobRunner);
    }

    private function createContext(?string $status, MessageInterface $message): Context
    {
        $context = new Context($this->createMock(SessionInterface::class));
        $context->setStatus($status);
        $context->setMessage($message);

        return $context;
    }

    public function testOnPostReceivedCancelsUniqueJobWhenStatusIsNull(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('isRedelivered')
            ->willReturn(false);
        $message->expects(self::once())
            ->method('getProperty')
            ->with(JobAwareTopicInterface::UNIQUE_JOB_NAME)
            ->willReturn('job-name');
        $message->expects(self::once())
            ->method('getMessageId')
            ->willReturn('message-id');

        $this->jobRunner->expects(self::once())
            ->method('cancelUniqueIfStatusNew')
            ->with('message-id', 'job-name');

        $this->extension->onPostReceived($this->createContext(null, $message));
    }

    public function testOnPostReceivedCancelsUniqueJobWhenStatusIsReject(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('isRedelivered')
            ->willReturn(false);

        $this->jobRunner->expects(self::once())
            ->method('cancelUniqueIfStatusNew');

        $this->extension->onPostReceived($this->createContext(MessageProcessorInterface::REJECT, $message));
    }

    public function testOnPostReceivedDoesNothingWhenStatusIsRequeue(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::never())
            ->method('isRedelivered');

        $this->jobRunner->expects(self::never())
            ->method('cancelUniqueIfStatusNew');

        $this->extension->onPostReceived($this->createContext(MessageProcessorInterface::REQUEUE, $message));
    }

    public function testOnPostReceivedDoesNothingWhenMessageIsRedelivered(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('isRedelivered')
            ->willReturn(true);

        $this->jobRunner->expects(self::never())
            ->method('cancelUniqueIfStatusNew');

        $this->extension->onPostReceived($this->createContext(null, $message));
    }

    public function testOnInterruptedCancelsUniqueJobWhenStatusIsReject(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('isRedelivered')
            ->willReturn(false);
        $message->expects(self::once())
            ->method('getProperty')
            ->with(JobAwareTopicInterface::UNIQUE_JOB_NAME)
            ->willReturn('job-name');
        $message->expects(self::once())
            ->method('getMessageId')
            ->willReturn('message-id');

        $this->jobRunner->expects(self::once())
            ->method('cancelUniqueIfStatusNew')
            ->with('message-id', 'job-name');

        $this->extension->onInterrupted($this->createContext(MessageProcessorInterface::REJECT, $message));
    }

    public function testOnInterruptedDoesNothingWhenStatusIsNotReject(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::never())
            ->method('isRedelivered');

        $this->jobRunner->expects(self::never())
            ->method('cancelUniqueIfStatusNew');

        $this->extension->onInterrupted($this->createContext(MessageProcessorInterface::ACK, $message));
    }
}
