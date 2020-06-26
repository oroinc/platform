<?php

namespace Oro\Component\MessageQueue\StatusCalculator;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
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

    /** @var array */
    private $childJobStatusCounts = [];

    /** @var Job */
    private $rootJob;

    /**
     * @param ManagerRegistry $doctrine
     * @param string $entityClass
     */
    public function __construct(ManagerRegistry $doctrine, string $entityClass)
    {
        $this->doctrine = $doctrine;
        $this->entityClass = $entityClass;
    }

    /**
     * @param Job $rootJob
     */
    public function init(Job $rootJob)
    {
        $this->rootJob = $rootJob;
        $this->childJobStatusCounts = $this->getJobRepository()->getChildStatusesWithJobCountByRootJob($rootJob);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateRootJobProgress()
    {
        if (empty($this->childJobStatusCounts)) {
            return 0;
        }

        $processed = 0;
        $childrenCount = 0;
        foreach ($this->childJobStatusCounts as $jobStatus => $childJobCount) {
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
        $this->childJobStatusCounts = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getChildrenInternalJobStatusCountList()
    {
        $internalJobStatusCountList = $this->getFullInternalStatusCountList([]);
        foreach ($this->childJobStatusCounts as $jobStatus => $childJobCount) {
            $internalStatus = $this->convertJobStatusToInternalStatus($jobStatus);
            if (false === $internalStatus) {
                $childJobIds = $this->getJobRepository()->getChildJobIdsByRootJobAndStatus($this->rootJob, $jobStatus);
                throw new \LogicException(sprintf(
                    'Got unsupported job status: ids: "%s" status: "%s"',
                    implode(', ', $childJobIds),
                    $jobStatus
                ));
            }

            $internalJobStatusCountList[$internalStatus] += $childJobCount;
        }

        return $internalJobStatusCountList;
    }

    /**
     * @return JobRepositoryInterface|ObjectRepository
     */
    private function getJobRepository(): JobRepositoryInterface
    {
        return $this->doctrine->getManagerForClass($this->entityClass)->getRepository($this->entityClass);
    }
}
