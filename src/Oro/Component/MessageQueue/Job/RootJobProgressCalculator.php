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
        $children = $rootJob->getChildJobs();
        $processed = 0;
        if (!($numberOfChildren = (count($children)))) {
            return;
        }

        foreach ($children as $child) {
            if (in_array($child->getStatus(), self::$stopStatuses)) {
                $processed++;
            }
        }
        $progress = round($processed / $numberOfChildren * 100, 2);
        $this->jobStorage->saveJob($rootJob, function (Job $rootJob) use ($progress) {
            if ($progress !== $rootJob->getJobProgress()) {
                $rootJob->setJobProgress($progress);
            }
        });
    }
}
