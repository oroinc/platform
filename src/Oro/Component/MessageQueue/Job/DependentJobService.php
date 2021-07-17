<?php

namespace Oro\Component\MessageQueue\Job;

/**
 * Save list of jobs that should be started after when root job was finished (all child jobs is processed).
 */
class DependentJobService
{
    /** @var JobManagerInterface */
    private $jobManager;

    public function __construct(JobManagerInterface $jobManager)
    {
        $this->jobManager = $jobManager;
    }

    public function createDependentJobContext(Job $job): DependentJobContext
    {
        return new DependentJobContext($job);
    }

    public function saveDependentJob(DependentJobContext $context): void
    {
        if (! $context->getJob()->isRoot()) {
            throw new \LogicException(sprintf(
                'Only root jobs allowed but got child. jobId: "%s"',
                $context->getJob()->getId()
            ));
        }

        $this->jobManager->saveJobWithLock($context->getJob(), static function (Job $job) use ($context) {
            $data = $job->getData();
            $data['dependentJobs'] = $context->getDependentJobs();

            $job->setData($data);
        });
    }
}
