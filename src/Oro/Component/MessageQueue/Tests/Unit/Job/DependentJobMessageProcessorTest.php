<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobMessageProcessor;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRepositoryInterface;
use Oro\Component\MessageQueue\Job\Topic\RootJobStoppedTopic;
use Oro\Component\MessageQueue\Transport\Message as TransportMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class DependentJobMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var JobRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRepository;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var DependentJobMessageProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->jobRepository = $this->createMock(JobRepositoryInterface::class);
        $entityClass = Job::class;
        $manager = $this->createMock(ManagerRegistry::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($this->jobRepository);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($manager);

        $this->processor = new DependentJobMessageProcessor(
            $this->producer,
            $doctrine,
            $entityClass,
            $this->logger
        );
    }

    public function testShouldReturnSubscribedTopicNames(): void
    {
        $this->assertEquals(
            [RootJobStoppedTopic::getName()],
            DependentJobMessageProcessor::getSubscribedTopics()
        );
    }

    public function testShouldLogCriticalAndRejectMessageIfJobEntityWasNotFound(): void
    {
        $this->jobRepository->expects($this->once())
            ->method('findJobById')
            ->with(12345);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Job was not found. id: "12345"');

        $message = new TransportMessage();
        $message->setBody(['jobId' => 12345]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldLogCriticalAndRejectMessageIfJobIsNotRoot(): void
    {
        $job = new Job();
        $job->setRootJob(new Job());

        $this->jobRepository->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->willReturn($job);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Expected root job but got child. id: "12345"');

        $message = new TransportMessage();
        $message->setBody(['jobId' => 12345]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldDoNothingIfDependentJobsAreMissing(): void
    {
        $job = new Job();

        $this->jobRepository->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->willReturn($job);

        $this->producer->expects($this->never())
            ->method('send');

        $message = new TransportMessage();
        $message->setBody(['jobId' => 12345]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldLogCriticalAndRejectMessageIfDependentJobTopicIsMissing(): void
    {
        $job = new Job();
        $job->setId(123);
        $job->setData([
            'dependentJobs' => [
                [],
            ]
        ]);

        $this->jobRepository->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->willReturn($job);

        $this->producer->expects($this->never())
            ->method('send');

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid dependent job data. job: "123", dependentJob: "[]"');

        $message = new TransportMessage();
        $message->setBody(['jobId' => 12345]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldLogCriticalAndRejectMessageIfDependentJobMessageIsMissing(): void
    {
        $job = new Job();
        $job->setId(123);
        $job->setData([
            'dependentJobs' => [
                [
                    'topic' => 'topic-name',
                ],
            ]
        ]);

        $this->jobRepository->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->willReturn($job);

        $this->producer->expects($this->never())
            ->method('send');

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid dependent job data. '.
             'job: "123", dependentJob: "{"topic":"topic-name"}"');

        $message = new TransportMessage();
        $message->setBody(['jobId' => 12345]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldPublishDependentMessage(): void
    {
        $job = new Job();
        $job->setId(123);
        $job->setData([
            'dependentJobs' => [
                [
                    'topic' => 'topic-name',
                    'message' => 'message',
                ],
            ]
        ]);

        $this->jobRepository->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->willReturn($job);

        /** @var Message $expectedMessage */
        $expectedMessage = null;
        $this->producer->expects($this->once())
            ->method('send')
            ->with('topic-name', $this->isInstanceOf(Message::class))
            ->willReturnCallback(static function ($topic, Message $message) use (&$expectedMessage) {
                $expectedMessage = $message;
            });

        $message = new TransportMessage();
        $message->setBody(['jobId' => 12345]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);

        $this->assertEquals('message', $expectedMessage->getBody());
        $this->assertNull($expectedMessage->getPriority());
    }

    public function testShouldPublishDependentMessageWithPriority(): void
    {
        $job = new Job();
        $job->setId(123);
        $job->setData([
            'dependentJobs' => [
                [
                    'topic' => 'topic-name',
                    'message' => 'message',
                    'priority' => 'priority',
                ],
            ]
        ]);

        $this->jobRepository->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->willReturn($job);

        /** @var Message $expectedMessage */
        $expectedMessage = null;
        $this->producer->expects($this->once())
            ->method('send')
            ->with('topic-name', $this->isInstanceOf(Message::class))
            ->willReturnCallback(static function ($topic, Message $message) use (&$expectedMessage) {
                $expectedMessage = $message;
            });

        $message = new TransportMessage();
        $message->setBody(['jobId' => 12345]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);

        $this->assertEquals('message', $expectedMessage->getBody());
        $this->assertEquals('priority', $expectedMessage->getPriority());
    }

    public function testShouldPublishDependentMessageWithAdditionalProperties(): void
    {
        $job = new Job();
        $job->setId(123);
        $job->setData([
            'dependentJobs' => [
                ['topic' => 'topic-name', 'message' => 'message']
            ]
        ]);
        $job->setProperties(['key' => 'value']);

        $this->jobRepository->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->willReturn($job);

        /** @var Message $expectedMessage */
        $expectedMessage = null;
        $this->producer->expects($this->once())
            ->method('send')
            ->with('topic-name', $this->isInstanceOf(Message::class))
            ->willReturnCallback(static function ($topic, Message $message) use (&$expectedMessage) {
                $expectedMessage = $message;
            });

        $message = new TransportMessage();
        $message->setBody(['jobId' => 12345]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);

        $this->assertEquals('message', $expectedMessage->getBody());
        $this->assertEquals(['key' => 'value'], $expectedMessage->getProperties());
    }
}
