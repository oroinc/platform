<?php
namespace Oro\Component\MessageQueue\Test;

use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Job\JobRunner as BaseJobRunner;

trait JobExtensionTrait
{
    /**
     * @return JobRunner
     */
    public function createJobRunner()
    {
        return new JobRunner();
    }

    /**
     * @param BaseJobRunner $jobRunner
     *
     * @return JobProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createJobProcessorStub(BaseJobRunner $jobRunner = null)
    {
        $jobRunner = $jobRunner ?: $this->createJobRunner();

        /** @var \PHPUnit_Framework_MockObject_MockObject $jobProcessorMock */
        $jobProcessorMock = $this->getMock(JobProcessor::class, [], [], '', false);
        $jobProcessorMock
            ->expects(self::any())
            ->method('createJobRunner')
            ->willReturn($jobRunner)
        ;

        return $jobProcessorMock;
    }
}
