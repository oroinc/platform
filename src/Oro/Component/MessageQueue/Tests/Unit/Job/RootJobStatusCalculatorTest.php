<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Checker\JobStatusChecker;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Job\RootJobStatusCalculator;
use Oro\Component\MessageQueue\StatusCalculator\AbstractStatusCalculator;
use Oro\Component\MessageQueue\StatusCalculator\StatusCalculatorResolver;

class RootJobStatusCalculatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $jobStorage;

    /** @var JobStatusChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $jobStatusChecker;

    /** @var StatusCalculatorResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $statusCalculatorResolver;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var RootJobStatusCalculator */
    private $rootJobStatusCalculator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->jobStorage = $this->createMock(JobStorage::class);
        $this->jobStatusChecker = $this->createMock(JobStatusChecker::class);
        $this->statusCalculatorResolver = $this->createMock(StatusCalculatorResolver::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->rootJobStatusCalculator = new RootJobStatusCalculator(
            $this->jobStorage,
            $this->jobStatusChecker,
            $this->statusCalculatorResolver,
            $this->messageProducer
        );
    }

    /**
     * @dataProvider jobDataProvider
     *
     * @param Job $passedJob
     * @param Job $expectedJob
     * @return void
     */
    public function testCalculateJobStoppedBeforeCalculate(Job $passedJob, Job $expectedJob): void
    {
        $dateTime = new \DateTime('-1 hour');
        $expectedJob->setLastActiveAt($dateTime);
        $this->jobStatusChecker
            ->expects($this->once())
            ->method('isJobStopped')
            ->with($expectedJob)
            ->willReturn(true);

        $this->jobStorage
            ->expects($this->never())
            ->method('saveJob');

        $this->statusCalculatorResolver
            ->expects($this->never())
            ->method('getCalculatorForRootJob');

        $this->messageProducer
            ->expects($this->never())
            ->method('send');

        $this->rootJobStatusCalculator->calculate($passedJob);

        $this->assertEquals($dateTime, $expectedJob->getLastActiveAt());
        $this->assertNull($expectedJob->getStoppedAt());
    }

    /**
     * @dataProvider jobDataProvider
     *
     * @param Job $passedJob
     * @param Job $expectedJob
     * @return void
     */
    public function testCalculateJobStoppedBeforeSave(Job $passedJob, Job $expectedJob): void
    {
        $dateTime = new \DateTime('-1 hour');
        $expectedJob->setLastActiveAt($dateTime);
        $this->jobStatusChecker
            ->expects($this->exactly(2))
            ->method('isJobStopped')
            ->withConsecutive(
                [$expectedJob],
                [$expectedJob]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );

        $this->jobStorage
            ->expects($this->once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $expectedJob, $callback) {
                $callback($expectedJob);
            });

        $this->statusCalculatorResolver
            ->expects($this->never())
            ->method('getCalculatorForRootJob');

        $this->messageProducer
            ->expects($this->never())
            ->method('send');

        $this->rootJobStatusCalculator->calculate($passedJob);

        $this->assertEquals($dateTime, $expectedJob->getLastActiveAt());
        $this->assertNull($expectedJob->getStoppedAt());
    }

    /**
     * @dataProvider jobDataProvider
     *
     * @param Job $passedJob
     * @param Job $expectedJob
     * @return void
     */
    public function testCalculateChangeJobStatusOnly(Job $passedJob, Job $expectedJob): void
    {
        $dateTime = new \DateTime('-1 hour');
        $expectedJob->setLastActiveAt($dateTime);
        $expectedJob->setJobProgress(0.5);
        $this->jobStatusChecker
            ->expects($this->exactly(4))
            ->method('isJobStopped')
            ->withConsecutive(
                [$expectedJob],
                [$expectedJob],
                [$expectedJob],
                [$expectedJob]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                false,
                false
            );

        $this->jobStorage
            ->expects($this->once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $expectedJob, $callback) {
                $callback($expectedJob);
            });

        $statusAndProgressCalculator = $this->createMock(AbstractStatusCalculator::class);
        $statusAndProgressCalculator->expects($this->once())
            ->method('calculateRootJobStatus')
            ->willReturn('oro.message_queue_job.status.running');
        $statusAndProgressCalculator->expects($this->once())
            ->method('calculateRootJobProgress')
            ->willReturn(0.5);
        $statusAndProgressCalculator->expects($this->once())
            ->method('clean');

        $this->statusCalculatorResolver
            ->expects($this->once())
            ->method('getCalculatorForRootJob')
            ->with($expectedJob)
            ->willReturn($statusAndProgressCalculator);

        $this->messageProducer
            ->expects($this->never())
            ->method('send');

        $this->rootJobStatusCalculator->calculate($passedJob);

        $this->assertGreaterThan($dateTime, $expectedJob->getLastActiveAt());
        $this->assertNull($expectedJob->getStoppedAt());
        $this->assertEquals(0.5, $expectedJob->getJobProgress());
        $this->assertEquals('oro.message_queue_job.status.running', $expectedJob->getStatus());
    }

    /**
     * @dataProvider jobDataProvider
     *
     * @param Job $passedJob
     * @param Job $expectedJob
     * @return void
     */
    public function testCalculateChangeJobStatusAndStop(Job $passedJob, Job $expectedJob): void
    {
        $dateTime = new \DateTime('-1 hour');
        $expectedJob->setId(75);
        $expectedJob->setLastActiveAt($dateTime);
        $expectedJob->setJobProgress(0.5);
        $this->jobStatusChecker
            ->expects($this->exactly(4))
            ->method('isJobStopped')
            ->withConsecutive(
                [$expectedJob],
                [$expectedJob],
                [$expectedJob],
                [$expectedJob]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                true,
                true
            );

        $this->jobStorage
            ->expects($this->once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $expectedJob, $callback) {
                $callback($expectedJob);
            });

        $statusAndProgressCalculator = $this->createMock(AbstractStatusCalculator::class);
        $statusAndProgressCalculator->expects($this->once())
            ->method('calculateRootJobStatus')
            ->willReturn('oro.message_queue_job.status.success');
        $statusAndProgressCalculator->expects($this->once())
            ->method('calculateRootJobProgress')
            ->willReturn(1);
        $statusAndProgressCalculator->expects($this->once())
            ->method('clean');

        $this->statusCalculatorResolver
            ->expects($this->once())
            ->method('getCalculatorForRootJob')
            ->with($expectedJob)
            ->willReturn($statusAndProgressCalculator);

        $this->messageProducer
            ->expects($this->once())
            ->method('send')
            ->with(
                'oro.message_queue.job.root_job_stopped',
                new Message(['jobId' => 75], MessagePriority::HIGH)
            );

        $this->rootJobStatusCalculator->calculate($passedJob);

        $this->assertGreaterThan($dateTime, $expectedJob->getLastActiveAt());
        $this->assertGreaterThan($dateTime, $expectedJob->getStoppedAt());
        $this->assertEquals(1, $expectedJob->getJobProgress());
        $this->assertEquals('oro.message_queue_job.status.success', $expectedJob->getStatus());
    }

    /**
     * @return array
     */
    public function jobDataProvider(): array
    {
        $rootJob = new Job();
        $rootJob->setName('root.job');
        $childJob = new Job();
        $childJob->setName('child.job');
        $childJob->setRootJob($rootJob);
        $rootJob->addChildJob($childJob);

        return [
            'with child job' => [
                'passedJob' => $childJob,
                'expectedJob' => $rootJob,
            ],
            'with root job' => [
                'passedJob' => $rootJob,
                'expectedJob' => $rootJob,
            ]
        ];
    }
}
