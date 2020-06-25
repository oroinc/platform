<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Job\CalculateRootJobStatusProcessor;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRepositoryInterface;
use Oro\Component\MessageQueue\Job\RootJobStatusCalculator;
use Oro\Component\MessageQueue\Job\RootJobStatusCalculatorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class CalculateRootJobStatusProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRepository;

    /** @var RootJobStatusCalculatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rootJobStatusCalculator;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var CalculateRootJobStatusProcessor */
    private $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->jobRepository = $this->createMock(JobRepositoryInterface::class);
        $this->rootJobStatusCalculator = $this->createMock(RootJobStatusCalculator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
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

        $this->processor = new CalculateRootJobStatusProcessor(
            $this->rootJobStatusCalculator,
            $doctrine,
            $entityClass,
            $this->logger
        );
    }

    public function testProcessWithInvalidMessage(): void
    {
        $this->jobRepository
            ->expects($this->never())
            ->method('findJobById');

        $this->rootJobStatusCalculator
            ->expects($this->never())
            ->method('calculate');

        $this->logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message. Job id is missing.');


        $message = new Message();
        $message->setBody(\json_encode([]));
        $session = $this->createMock(SessionInterface::class);
        $result = $this->processor->process($message, $session);

        $this->assertEquals('oro.message_queue.consumption.reject', $result);
    }

    public function testProcessJobNotFound(): void
    {
        $this->jobRepository
            ->expects($this->once())
            ->method('findJobById')
            ->with(47)
            ->willReturn(null);

        $this->rootJobStatusCalculator
            ->expects($this->never())
            ->method('calculate');

        $this->logger
            ->expects($this->once())
            ->method('critical')
            ->with('Job was not found. id: "47"');


        $message = new Message();
        $message->setBody(\json_encode(['jobId' => 47]));
        $session = $this->createMock(SessionInterface::class);
        $result = $this->processor->process($message, $session);

        $this->assertEquals('oro.message_queue.consumption.reject', $result);
    }

    public function testProcess(): void
    {
        $job = new Job();
        $job->setId(47);

        $this->jobRepository
            ->expects($this->once())
            ->method('findJobById')
            ->with(47)
            ->willReturn($job);

        $this->rootJobStatusCalculator
            ->expects($this->once())
            ->method('calculate')
            ->with($job);

        $this->logger
            ->expects($this->never())
            ->method('critical');


        $message = new Message();
        $message->setBody(\json_encode(['jobId' => 47]));
        $session = $this->createMock(SessionInterface::class);
        $result = $this->processor->process($message, $session);

        $this->assertEquals('oro.message_queue.consumption.ack', $result);
    }

    public function testGetSubscribedTopics(): void
    {
        $this->assertEquals(
            ['oro.message_queue.job.calculate_root_job_status'],
            CalculateRootJobStatusProcessor::getSubscribedTopics()
        );
    }
}
