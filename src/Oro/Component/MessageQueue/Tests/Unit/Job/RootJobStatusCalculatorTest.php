<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Checker\JobStatusChecker;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobManagerInterface;
use Oro\Component\MessageQueue\Job\RootJobStatusCalculator;
use Oro\Component\MessageQueue\Job\Topic\RootJobStoppedTopic;
use Oro\Component\MessageQueue\StatusCalculator\AbstractStatusCalculator;
use Oro\Component\MessageQueue\StatusCalculator\StatusCalculatorResolver;

class RootJobStatusCalculatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $jobManager;

    /** @var JobStatusChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $jobStatusChecker;

    /** @var StatusCalculatorResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $statusCalculatorResolver;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var RootJobStatusCalculator */
    private $rootJobStatusCalculator;

    protected function setUp(): void
    {
        $this->jobManager = $this->createMock(JobManagerInterface::class);
        $this->jobStatusChecker = new JobStatusChecker();
        $this->statusCalculatorResolver = $this->createMock(StatusCalculatorResolver::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->rootJobStatusCalculator = new RootJobStatusCalculator(
            $this->jobManager,
            $this->jobStatusChecker,
            $this->statusCalculatorResolver,
            $this->messageProducer,
            $this->doctrine
        );

        $this->jobManager->expects($this->any())
            ->method('saveJobWithLock')
            ->willReturnCallback(fn (Job $job, callable $callback) => $callback($job));
    }

    /**
     * @dataProvider testJobStoppedBeforeCalculateProvider
     */
    public function testJobStoppedBeforeCalculate(Job $job): void
    {
        $this->assertObjectManaged();
        $this->statusCalculatorResolver->expects($this->never())
            ->method('getCalculatorForRootJob');

        $this->messageProducer->expects($this->never())
            ->method('send');

        $this->rootJobStatusCalculator->calculate($job);
        $this->assertNull($job->getLastActiveAt());
    }

    public function testJobStoppedBeforeCalculateProvider(): \Generator
    {
        yield Job::STATUS_SUCCESS => [$this->getJob('job', Job::STATUS_SUCCESS)];
        yield Job::STATUS_FAILED => [$this->getJob('job', Job::STATUS_FAILED)];
        yield Job::STATUS_CANCELLED => [$this->getJob('job', Job::STATUS_CANCELLED)];
        yield Job::STATUS_STALE => [$this->getJob('job', Job::STATUS_STALE)];
    }

    public function testChangeJobStatus(): void
    {
        $this->assertObjectManaged();
        $job = $this->getJob();

        $statusAndProgressCalculator = $this->createMock(AbstractStatusCalculator::class);
        $statusAndProgressCalculator->expects($this->once())
            ->method('calculateRootJobStatus')
            ->willReturn(Job::STATUS_RUNNING);
        $statusAndProgressCalculator->expects($this->once())
            ->method('clean');

        $this->statusCalculatorResolver->expects($this->once())
            ->method('getCalculatorForRootJob')
            ->with($job)
            ->willReturn($statusAndProgressCalculator);

        $this->messageProducer->expects($this->never())
            ->method('send');

        $this->rootJobStatusCalculator->calculate($job);

        $this->assertNotNull($job->getLastActiveAt());
        $this->assertNull($job->getStoppedAt());
        $this->assertEquals(Job::STATUS_RUNNING, $job->getStatus());
    }

    public function testChangeJobStatusAndStop(): void
    {
        $this->assertObjectManaged();
        $job = $this->getJob('job', Job::STATUS_RUNNING, 1);

        $statusAndProgressCalculator = $this->createMock(AbstractStatusCalculator::class);
        $statusAndProgressCalculator->expects($this->once())
            ->method('calculateRootJobStatus')
            ->willReturn(Job::STATUS_SUCCESS);
        $statusAndProgressCalculator->expects($this->once())
            ->method('calculateRootJobProgress')
            ->willReturn(1);
        $statusAndProgressCalculator->expects($this->once())
            ->method('clean');

        $this->statusCalculatorResolver->expects($this->once())
            ->method('getCalculatorForRootJob')
            ->with($job)
            ->willReturn($statusAndProgressCalculator);

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(RootJobStoppedTopic::getName(), new Message(['jobId' => 1], MessagePriority::HIGH));

        $this->rootJobStatusCalculator->calculate($job);

        $this->assertNotNull($job->getLastActiveAt());
        $this->assertNotNull($job->getStoppedAt());
        $this->assertEquals(1, $job->getJobProgress());
        $this->assertEquals(Job::STATUS_SUCCESS, $job->getStatus());
    }

    public function testCalculateChildJobsWithStop(): void
    {
        $this->assertObjectManaged();

        $rootJob = $this->getJob(name: 'Root Job', id: 1);
        $child1 = $this->getJob(name: 'Child Job 1', rootJob: $rootJob);
        $child2 = $this->getJob(name: 'Child Job 2', rootJob: $rootJob);
        $rootJob->setChildJobs([$child1, $child2]);

        $statusAndProgressCalculator = $this->createMock(AbstractStatusCalculator::class);
        $statusAndProgressCalculator->expects($this->once())
            ->method('calculateRootJobStatus')
            ->willReturn(Job::STATUS_SUCCESS);
        $statusAndProgressCalculator->expects($this->once())
            ->method('calculateRootJobProgress')
            ->willReturn(1);
        $statusAndProgressCalculator->expects($this->once())
            ->method('clean');

        $this->statusCalculatorResolver->expects($this->once())
            ->method('getCalculatorForRootJob')
            ->with($rootJob)
            ->willReturn($statusAndProgressCalculator);

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(RootJobStoppedTopic::getName(), new Message(['jobId' => 1], MessagePriority::HIGH));

        // Because all consumers wait until at least one consumer updates the status of the 'job'
        // and unlocks the record, we can simulate parallel processors due to the loop.
        foreach ($rootJob->getChildJobs() as $childJob) {
            $this->rootJobStatusCalculator->calculate($childJob);
        }

        $this->assertNotNull($rootJob->getLastActiveAt());
        $this->assertNotNull($rootJob->getStoppedAt());
        $this->assertEquals(1, $rootJob->getJobProgress());
        $this->assertEquals(Job::STATUS_SUCCESS, $rootJob->getStatus());
    }

    private function assertObjectManaged(): void
    {
        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->any())
            ->method('contains')
            ->willReturn(true);
        $manager->expects($this->any())
            ->method('refresh');

        $this->doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($manager);
    }

    private function getJob(
        string $name = 'Job',
        ?string $status = Job::STATUS_NEW,
        ?int $id = null,
        ?Job $rootJob = null
    ): Job {
        $job = new Job();
        $job->setName($name);
        $job->setStatus($status);
        if ($id) {
            $job->setId($id);
        }
        if ($rootJob) {
            $job->setRootJob($rootJob);
        }

        return $job;
    }
}
