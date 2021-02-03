<?php

namespace Oro\Component\MessageQueue\StatusCalculator;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRepositoryInterface;

/**
 * Calculate root job status and root job progress with DB queries.
 */
class QueryCalculator extends AbstractStatusCalculator
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var string */
    private $entityClass;

    /** @var Job */
    private $rootJob;

    public function __construct(ManagerRegistry $doctrine, string $entityClass)
    {
        $this->doctrine = $doctrine;
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function init(Job $rootJob)
    {
        $this->rootJob = $rootJob;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateRootJobProgress()
    {
        $childJobStatusCounts = $this->getJobRepository()->getChildStatusesWithJobCountByRootJob($this->rootJob);

        $processed = 0;
        $childrenCount = 0;
        foreach ($childJobStatusCounts as $jobStatus => $childJobCount) {
            if ($this->jobStatusChecker->isFinishedJobStatus($jobStatus)) {
                $processed += $childJobCount;
            }

            $childrenCount += $childJobCount;
        }

        return $this->doJobProgressCalculation($processed, $childrenCount);
    }

    /**
     * {@inheritdoc}
     */
    public function clean()
    {
        $this->rootJob = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getChildrenInternalJobStatusCountList()
    {
        $childJobStatusCounts = $this->getJobRepository()->getChildStatusesWithJobCountByRootJob($this->rootJob);
        $internalJobStatusCountList = $this->getFullInternalStatusCountList([]);
        foreach ($childJobStatusCounts as $jobStatus => $childJobCount) {
            $internalStatus = $this->convertJobStatusToInternalStatus($jobStatus);
            if (false === $internalStatus) {
                $childJobIds = $this->getJobRepository()->getChildJobIdsByRootJobAndStatus($this->rootJob, $jobStatus);
                throw new \LogicException(
                    sprintf(
                        'Got unsupported job status: ids: "%s" status: "%s"',
                        implode(', ', $childJobIds),
                        $jobStatus
                    )
                );
            }

            $internalJobStatusCountList[$internalStatus] += $childJobCount;
        }

        return $internalJobStatusCountList;
    }

    private function getJobRepository(): JobRepositoryInterface
    {
        return $this->doctrine->getManagerForClass($this->entityClass)->getRepository($this->entityClass);
    }
}
