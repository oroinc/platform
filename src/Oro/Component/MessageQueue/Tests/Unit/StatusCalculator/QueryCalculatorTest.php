<?php

namespace Oro\Component\MessageQueue\Tests\Unit\StatusCalculator;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Checker\JobStatusChecker;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRepositoryInterface;
use Oro\Component\MessageQueue\StatusCalculator\QueryCalculator;

class QueryCalculatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRepository;

    /** @var QueryCalculator */
    private $queryCalculator;

    protected function setUp(): void
    {
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

        $this->queryCalculator = new QueryCalculator($doctrine, $entityClass);
        $this->queryCalculator->setJobStatusChecker(new JobStatusChecker());
    }

    public function calculateProgressProvider(): array
    {
        return [
            [
                [
                    Job::STATUS_NEW => 2,
                ],
                0,
            ],
            [
                [
                    Job::STATUS_RUNNING => 1,
                    Job::STATUS_NEW => 1,
                ],
                0,
            ],
            [
                [
                    Job::STATUS_SUCCESS => 1,
                    Job::STATUS_NEW => 1,
                ],
                0.5,
            ],
            [
                [
                    Job::STATUS_SUCCESS => 1,
                    Job::STATUS_RUNNING => 1,
                    Job::STATUS_NEW => 1,
                ],
                0.3333,
            ],
            [
                [
                    Job::STATUS_SUCCESS => 1,
                    Job::STATUS_FAILED => 1,
                    Job::STATUS_RUNNING => 1,
                ],
                0.6667,
            ],
            [
                [
                    Job::STATUS_SUCCESS => 2,
                    Job::STATUS_FAILED => 1,
                ],
                1,
            ],
            [
                [
                    Job::STATUS_SUCCESS => 1,
                    Job::STATUS_FAILED => 1,
                    Job::STATUS_CANCELLED => 1,
                ],
                0.6667,
            ],
            [
                [
                    Job::STATUS_SUCCESS => 1,
                    Job::STATUS_STALE => 2,
                ],
                0.3333,
            ],
        ];
    }

    /**
     * @dataProvider calculateProgressProvider
     */
    public function testCalculateRootJobProgress(array $statuses, float $expectedStatusProgress): void
    {
        $rootJob = new Job();
        $this->jobRepository
            ->expects($this->once())
            ->method('getChildStatusesWithJobCountByRootJob')
            ->with($rootJob)
            ->willReturn($statuses);

        $this->queryCalculator->init($rootJob);
        $statusProgress = $this->queryCalculator->calculateRootJobProgress();
        $this->assertEquals($expectedStatusProgress, $statusProgress);
    }

    public function statusCalculateProvider(): array
    {
        return [
            [
                [
                    Job::STATUS_NEW => 2,
                ],
                Job::STATUS_NEW,
            ],
            [
                [
                    Job::STATUS_RUNNING => 1,
                    Job::STATUS_NEW => 1,
                ],
                Job::STATUS_RUNNING,
            ],
            [
                [
                    Job::STATUS_SUCCESS => 1,
                    Job::STATUS_NEW => 1,
                ],
                Job::STATUS_RUNNING,
            ],
            [
                [
                    Job::STATUS_SUCCESS => 1,
                    Job::STATUS_RUNNING => 1,
                    Job::STATUS_NEW => 1,
                ],
                Job::STATUS_RUNNING,
            ],
            [
                [
                    Job::STATUS_SUCCESS => 1,
                    Job::STATUS_FAILED => 1,
                    Job::STATUS_RUNNING => 1,
                ],
                Job::STATUS_RUNNING,
            ],
            [
                [
                    Job::STATUS_SUCCESS => 2,
                    Job::STATUS_FAILED => 1,
                ],
                Job::STATUS_FAILED,
            ],
            [
                [
                    Job::STATUS_SUCCESS => 1,
                    Job::STATUS_FAILED => 1,
                    Job::STATUS_CANCELLED => 1,
                ],
                Job::STATUS_CANCELLED,
            ]
        ];
    }

    /**
     * @dataProvider statusCalculateProvider
     */
    public function testCalculateRootJobStatus(array $statuses, string $expectedStatus): void
    {
        $rootJob = new Job();
        $this->jobRepository
            ->expects($this->once())
            ->method('getChildStatusesWithJobCountByRootJob')
            ->with($rootJob)
            ->willReturn($statuses);

        $this->queryCalculator->init($rootJob);
        $status = $this->queryCalculator->calculateRootJobStatus();
        $this->assertEquals($expectedStatus, $status);
    }
}
