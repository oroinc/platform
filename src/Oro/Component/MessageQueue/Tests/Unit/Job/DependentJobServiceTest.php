<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;

class DependentJobServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldThrowIfRootJobIsNotSet()
    {
        $dependentJob = new DependentJobService();

        $this->setExpectedException(\LogicException::class, 'Root job is not set');

        $dependentJob->addDependentJob('', '');
    }

    public function testShouldAddDependentJobs()
    {
        $job = new Job();

        $dependentJob = new DependentJobService(null, $job);

        $dependentJob->addDependentJob('topic1', 'message1', 'priority1');
        $dependentJob->addDependentJob('topic2', 'message2');

        $expectedData = [
            'dependentJobs' => [
                [
                    'topic' => 'topic1',
                    'message' => 'message1',
                    'priority' => 'priority1',
                ],
                [
                    'topic' => 'topic2',
                    'message' => 'message2',
                ],
            ]
        ];

        $this->assertEquals($expectedData, $job->getData());
    }

    public function testShouldThrowIfRootJobIsSet()
    {
        $dependentJob = new DependentJobService(null, new Job());

        $this->setExpectedException(\LogicException::class, 'Is not allowed to call method if rootJob is set');

        $dependentJob->setDependentJob(new Job(), function () {

        });
    }

    public function testShouldThrowIfJobStorageIsNotSet()
    {
        $dependentJob = new DependentJobService(null, null);

        $this->setExpectedException(\LogicException::class, 'Job storage is not set');

        $dependentJob->setDependentJob(new Job(), function () {

        });
    }

    public function testShouldThrowIfJobIsNotRootJob()
    {
        $dependentJob = new DependentJobService($this->createJobStorageMock(), null);

        $this->setExpectedException(\LogicException::class, 'Only root jobs allowed but got child. id:"1234"');

        $job = new Job();
        $job->setId(1234);
        $job->setRootJob(new Job());

        $dependentJob->setDependentJob($job, function () {

        });
    }

    public function testShouldCallClosureAndSaveJob()
    {
        $job = new Job();

        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('saveJob')
            ->with($job, $this->isInstanceOf(\Closure::class))
            ->will($this->returnCallback(function (Job $job, $callback) {
                $callback($job);

                return true;
            }))
        ;

        $dependentJob = new DependentJobService($jobStorage, null);

        $childDependentJob = null;
        $dependentJob->setDependentJob($job, function (DependentJobService $dependentJob) use (&$childDependentJob) {
            $childDependentJob = $dependentJob;
        });

        $this->assertInstanceOf(DependentJobService::class, $childDependentJob);
        $this->assertNotSame($childDependentJob, $dependentJob);
    }

    public function testShouldClearDependentJobsBeforeCallback()
    {
        $job = new Job();
        $job->setData([
            'another-key' => 'another-value',
            'dependentJobs' => ['key' => 'value'],
        ]);

        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('saveJob')
            ->with($job, $this->isInstanceOf(\Closure::class))
            ->will($this->returnCallback(function (Job $job, $callback) {
                $callback($job);

                return true;
            }))
        ;

        $dependentJob = new DependentJobService($jobStorage, null);

        $dependentJob->setDependentJob($job, function () {

        });

        $expectedData = [
            'another-key' => 'another-value',
            'dependentJobs' => [],
        ];

        $this->assertEquals($expectedData, $job->getData());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobStorage
     */
    private function createJobStorageMock()
    {
        return $this->getMock(JobStorage::class, [], [], '', false);
    }
}
