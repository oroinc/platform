<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Job\CalculateRootJobProgressService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;

class CalculateRootJobProgressServiceTest extends \PHPUnit_Framework_TestCase
{

    public function calculateProgressProvider()
    {
        return [
            [[Job::STATUS_NEW, Job::STATUS_RUNNING], 0],
            [[Job::STATUS_SUCCESS, Job::STATUS_RUNNING, Job::STATUS_RUNNING], 33.33],
            [[Job::STATUS_SUCCESS, Job::STATUS_FAILED, Job::STATUS_CANCELLED], 100],
        ];
    }

    /**
     * @dataProvider calculateProgressProvider
     */
    public function testShouldCalculateRootJobProgress($statuses, $expectedProgress)
    {
        $rootJob = new Job();
        $rootJob->setId(123);

        foreach ($statuses as $status) {
            $childJob = new Job();
            $childJob->setRootJob($rootJob);
            $childJob->setStatus($status);
            $rootJob->addChildJob($childJob);
            $rootJob->addChildJob($childJob);
        }

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->will($this->returnCallback(function (Job $job, $callback) {
                $callback($job);
            }));

        $self = new CalculateRootJobProgressService($storage);
        $self->calculateRootJobProgress($childJob);
        $this->assertEquals($rootJob->getJobProgress(), $expectedProgress);

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->will($this->returnCallback(function (Job $job, $callback) {
                $callback($job);
            }));

        $self = new CalculateRootJobProgressService($storage);
        $self->calculateRootJobProgress($rootJob);
        $this->assertEquals($rootJob->getJobProgress(), $expectedProgress);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobStorage
     */
    private function createJobStorageMock()
    {
        return $this->createMock(JobStorage::class);
    }
}
