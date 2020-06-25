<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobManagerInterface;

class DependentJobServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobManagerInterface */
    private $jobManager;

    /** @var DependentJobService */
    private $dependentJobService;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->jobManager = $this->createMock(JobManagerInterface::class);
        $this->dependentJobService = new DependentJobService($this->jobManager);
    }

    public function testSaveDependentJobLogicException(): void
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());

        $context = new DependentJobContext($job);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Only root jobs allowed but got child. jobId: "12345"');
        $this->dependentJobService->saveDependentJob($context);
    }

    public function testSaveDependentJob(): void
    {
        $job = new Job();
        $job->setId(12345);

        $this->jobManager
            ->expects($this->once())
            ->method('saveJobWithLock')
            ->willReturnCallback(static function (Job $job, $callback) {
                $callback($job);

                return true;
            });

        $context = new DependentJobContext($job);
        $context->addDependentJob('job-topic', 'job-message', 'job-priority');

        $this->dependentJobService->saveDependentJob($context);

        $expectedDependentJobs = [
            'dependentJobs' => [
                [
                    'topic' => 'job-topic',
                    'message' => 'job-message',
                    'priority' => 'job-priority',
                ]
            ]
        ];

        $this->assertEquals($expectedDependentJobs, $job->getData());
    }
}
