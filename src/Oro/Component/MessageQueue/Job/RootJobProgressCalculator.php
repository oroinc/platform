<?php
namespace Oro\Component\MessageQueue\Job;

class RootJobProgressCalculator
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @param JobStorage $jobStorage
     */
    public function __construct(JobStorage $jobStorage)
    {
        $this->jobStorage = $jobStorage;
    }

    /**
     * @var array
     */
    protected static $stopStatuses = [Job::STATUS_SUCCESS, Job::STATUS_FAILED, Job::STATUS_CANCELLED];

    /**
     * @param Job $job
     */
    public function calculate(Job $job)
    {
        $rootJob = $job->isRoot() ? $job : $job->getRootJob();
        $rootJob->setLastActiveAt(new \DateTime());
        $children = $rootJob->getChildJobs();
        $numberOfChildren = count($children);
        if (0 === $numberOfChildren) {
            return;
        }

        $processed = 0;
        foreach ($children as $child) {
            if (in_array($child->getStatus(), self::$stopStatuses, true)) {
                $processed++;
            }
        }

        $progress = round($processed / $numberOfChildren, 4);
        $this->jobStorage->saveJob($rootJob, function (Job $rootJob) use ($progress) {
            if ($progress !== $rootJob->getJobProgress()) {
                $rootJob->setJobProgress($progress);
            }
        });
    }
}
