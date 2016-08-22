<?php
namespace Oro\Component\MessageQueue\Job;

class DependentJobService
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @param JobStorage|null $jobStorage
     */
    public function __construct(JobStorage $jobStorage)
    {
        $this->jobStorage = $jobStorage;
    }

    public function saveDependentJob(DependentJobContext $context)
    {
        if (! $context->getJob()->isRoot()) {
            throw new \LogicException('Only root job allowed');
        }

        $this->jobStorage->saveJob($context->getJob(), function (Job $job) use ($context) {
            $data = $job->getData();
            $data['dependentJobs'] = $context->getDependentJobs();

            $job->setData($data);
        });
    }
}
