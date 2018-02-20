<?php

namespace Oro\Component\MessageQueue\Tests\Unit\StatusCalculator;

use Oro\Component\MessageQueue\Checker\JobStatusChecker;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\StatusCalculator\QueryCalculator;

class QueryCalculatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var QueryCalculator */
    private $queryCalculator;

    /** @var JobStorage|\PHPUnit_Framework_MockObject_MockObject */
    private $jobStorage;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->jobStorage = $this->createMock(JobStorage::class);

        $jobStatusChecker = new JobStatusChecker();
        $this->queryCalculator = new QueryCalculator($this->jobStorage);
        $this->queryCalculator->setJobStatusChecker($jobStatusChecker);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->queryCalculator);
    }

    /**
     * @return array
     */
    public function calculateProgressProvider()
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
     *
     * @param array   $statuses
     * @param float  $expectedStatusProgress
     */
    public function testCalculateRootJobProgress(array $statuses, $expectedStatusProgress)
    {
        $rootJob = new Job();
        $this->jobStorage
            ->expects($this->once())
            ->method('getChildStatusesWithJobCountByRootJob')
            ->with($rootJob)
            ->willReturn($statuses);

        $this->queryCalculator->init($rootJob);
        $statusProgress = $this->queryCalculator->calculateRootJobProgress();
        $this->assertEquals($expectedStatusProgress, $statusProgress);
    }

    /**
     * @return array
     */
    public function statusCalculateProvider()
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
     *
     * @param array   $statuses
     * @param string  $expectedStatus
     */
    public function testCalculateRootJobStatus(array $statuses, $expectedStatus)
    {
        $rootJob = new Job();
        $this->jobStorage
            ->expects($this->once())
            ->method('getChildStatusesWithJobCountByRootJob')
            ->with($rootJob)
            ->willReturn($statuses);

        $this->queryCalculator->init($rootJob);
        $status = $this->queryCalculator->calculateRootJobStatus();
        $this->assertEquals($expectedStatus, $status);
    }
}
