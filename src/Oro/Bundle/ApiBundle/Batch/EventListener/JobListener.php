<?php

namespace Oro\Bundle\ApiBundle\Batch\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Batch\Async\AsyncOperationManager;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Event\BeforeSaveJobEvent;

/**
 * Synchronizes an asynchronous operation with the related MQ job.
 */
class JobListener
{
    private const OPERATION_ID = 'api_operation_id';
    private const SUMMARY = 'summary';
    private const EXTRA_CHUNK = 'extra_chunk';
    private const AGGREGATE_TIME = 'aggregateTime';
    private const READ_COUNT = 'readCount';
    private const WRITE_COUNT = 'writeCount';
    private const ERROR_COUNT = 'errorCount';
    private const CREATE_COUNT = 'createCount';
    private const UPDATE_COUNT = 'updateCount';

    private ManagerRegistry $doctrine;
    private AsyncOperationManager $asyncOperationManager;

    public function __construct(ManagerRegistry $doctrine, AsyncOperationManager $asyncOperationManager)
    {
        $this->doctrine = $doctrine;
        $this->asyncOperationManager = $asyncOperationManager;
    }

    public function onBeforeSaveJob(BeforeSaveJobEvent $event): void
    {
        /** @var Job $job */
        $job = $event->getJob();
        if (!$this->isRootJobUpdate($job)) {
            return;
        }

        $data = $job->getData();
        if (!isset($data[self::OPERATION_ID])) {
            return;
        }

        $operationId = $data[self::OPERATION_ID];
        $operation = $this->doctrine->getManagerForClass(AsyncOperation::class)
            ->find(AsyncOperation::class, $operationId);
        if (null === $operation) {
            return;
        }

        $this->asyncOperationManager->updateOperation($operationId, function () use ($operation, $job) {
            return $this->updateOperation($operation, $job);
        });
    }

    private function isRootJobUpdate(Job $job): bool
    {
        return $job->isRoot() && null !== $job->getId();
    }

    private function updateOperation(AsyncOperation $operation, Job $job): array
    {
        $data = [];
        $jobId = $job->getId();
        if ($operation->getJobId() !== $jobId) {
            $data['jobId'] = $jobId;
        }
        $progress = $job->getJobProgress();
        if (null !== $progress && $progress >= 0 && $operation->getProgress() !== $progress) {
            $data['progress'] = $progress;
        }
        $status = $this->getOperationStatus($job->getStatus());
        if ($status && $operation->getStatus() !== $status) {
            $data['status'] = $status;
            if (AsyncOperation::STATUS_SUCCESS === $status) {
                $data['progress'] = 1;
                $summary = $this->getTotalSummary($job, $operation);
                $data['summary'] = $summary;
                $data['hasErrors'] = ($summary[self::ERROR_COUNT] ?? 0) > 0;
            } elseif (AsyncOperation::STATUS_FAILED === $status) {
                $data['summary'] = $this->getTotalSummary($job, $operation);
                $data['hasErrors'] = true;
            }
        }

        return $data;
    }

    private function getOperationStatus(string $jobStatus): ?string
    {
        switch ($jobStatus) {
            case Job::STATUS_NEW:
            case Job::STATUS_RUNNING:
            case Job::STATUS_FAILED_REDELIVERED:
                return AsyncOperation::STATUS_RUNNING;
            case Job::STATUS_SUCCESS:
                return AsyncOperation::STATUS_SUCCESS;
            case Job::STATUS_FAILED:
            case Job::STATUS_STALE:
                return AsyncOperation::STATUS_FAILED;
            case Job::STATUS_CANCELLED:
                return AsyncOperation::STATUS_CANCELLED;
        }

        return null;
    }

    private function getTotalSummary(Job $job, AsyncOperation $operation): array
    {
        $totalSummary = [
            self::AGGREGATE_TIME => 0,
            self::READ_COUNT     => 0,
            self::WRITE_COUNT    => 0,
            self::ERROR_COUNT    => 0,
            self::CREATE_COUNT   => 0,
            self::UPDATE_COUNT   => 0
        ];
        $childJobs = $job->getChildJobs();
        foreach ($childJobs as $childJob) {
            $childJobData = $childJob->getData();
            if (!\array_key_exists(self::SUMMARY, $childJobData)) {
                continue;
            }
            $chunkSummary = $childJobData[self::SUMMARY];
            if (!\is_array($chunkSummary)) {
                continue;
            }
            if (isset($childJobData[self::EXTRA_CHUNK]) && $childJobData[self::EXTRA_CHUNK]) {
                unset($chunkSummary[self::READ_COUNT]);
            }
            self::mergeSummary($totalSummary, $chunkSummary);
        }

        $operationSummary = $operation->getSummary();
        if ($operationSummary) {
            self::mergeSummary($totalSummary, $operationSummary);
        }

        return $totalSummary;
    }

    private static function mergeSummary(array &$summary, array $toMerge): void
    {
        self::mergeSummaryItem($summary, $toMerge, self::AGGREGATE_TIME);
        self::mergeSummaryItem($summary, $toMerge, self::READ_COUNT);
        self::mergeSummaryItem($summary, $toMerge, self::WRITE_COUNT);
        self::mergeSummaryItem($summary, $toMerge, self::ERROR_COUNT);
        self::mergeSummaryItem($summary, $toMerge, self::CREATE_COUNT);
        self::mergeSummaryItem($summary, $toMerge, self::UPDATE_COUNT);
    }

    private static function mergeSummaryItem(array &$summary, array $toMerge, string $itemName): void
    {
        if (isset($toMerge[$itemName])) {
            $summary[$itemName] += $toMerge[$itemName];
        }
    }
}
