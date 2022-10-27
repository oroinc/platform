<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobManagerInterface;

class DependentJobServiceTest extends \PHPUnit\Framework\TestCase
{
    private JobManagerInterface|\PHPUnit\Framework\MockObject\MockObject $jobManager;

    private DependentJobService $dependentJobService;

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
            ->expects(self::once())
            ->method('saveJobWithLock')
            ->willReturnCallback(static fn (Job $job, $callback) => $callback($job));

        $context = new DependentJobContext($job);
        $context->addDependentJob('job-topic', 'job-message', 'job-priority');

        $this->dependentJobService->saveDependentJob($context);

        $expectedDependentJobs = [
            'dependentJobs' => [
                [
                    'topic' => 'job-topic',
                    'message' => 'job-message',
                    'priority' => 'job-priority',
                ],
            ],
        ];

        self::assertEquals($expectedDependentJobs, $job->getData());
    }

    public function testAddDependentMessages(): void
    {
        $job = new Job();
        $job->setId(12345);

        $this->jobManager
            ->expects(self::once())
            ->method('saveJobWithLock')
            ->willReturnCallback(static fn (Job $job, $callback) => $callback($job));

        $messages = [
            'topic1' => new Message(['sample-key1' => 'sample-value1'], MessagePriority::HIGH),
            'topic2' => new Message(['sample-key2' => 'sample-value2'], MessagePriority::LOW),
        ];
        $this->dependentJobService->addDependentMessages($job, $messages);

        $expectedDependentJobs = [
            'dependentJobs' => [
                [
                    'topic' => 'topic1',
                    'message' => $messages['topic1'],
                    'priority' => null,
                ],
                [
                    'topic' => 'topic2',
                    'message' => $messages['topic2'],
                    'priority' => null,
                ],
            ],
        ];

        self::assertEquals($expectedDependentJobs, $job->getData());
    }
}
