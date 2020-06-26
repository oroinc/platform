<?php

namespace Oro\Component\MessageQueue\Job;

/**
 * Save list of jobs that should be started after when root job was finished (all child jobs is processed).
 */
class DependentJobService
{
    /** @var JobManagerInterface */
    private $jobManager;

    /**
     * @param JobStorage $jobStorage
     */
    public function __construct(JobStorage $jobStorage)
    {
    }

    /**
     * @param JobManagerInterface $jobManager
     */
    public function setJobManager(JobManagerInterface $jobManager): void
    {
        $this->jobManager = $jobManager;
    }

    /**
     * @param Job $job
     *
     * @return DependentJobContext
     */
    public function createDependentJobContext(Job $job)
    {
        return new DependentJobContext($job);
    }

    /**
     * @param DependentJobContext $context
     */
    public function saveDependentJob(DependentJobContext $context)
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
