<?php

namespace Oro\Bundle\ApiBundle\Batch\EventListener;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Event\BeforeSaveJobEvent;

/**
 * Synchronizes an asynchronous operation with the related MQ job.
 */
class JobListener
{
    private const OPERATION_ID   = 'api_operation_id';
    private const SUMMARY        = 'summary';
    private const EXTRA_CHUNK    = 'extra_chunk';
    private const AGGREGATE_TIME = 'aggregateTime';
    private const READ_COUNT     = 'readCount';
    private const WRITE_COUNT    = 'writeCount';
    private const ERROR_COUNT    = 'errorCount';
    private const CREATE_COUNT   = 'createCount';
    private const UPDATE_COUNT   = 'updateCount';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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

        $em = $this->doctrineHelper->getEntityManager(AsyncOperation::class);
        $operation = $em->find(AsyncOperation::class, $data[self::OPERATION_ID]);
        if (null !== $operation && $this->updateOperation($operation, $job)) {
            $uow = $em->getUnitOfWork();
            $uow->clearEntityChangeSet(spl_object_hash($operation));
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(AsyncOperation::class), $operation);
            $uow->commit($operation);
        }
    }

    private function isRootJobUpdate(Job $job): bool
    {
        return $job->isRoot() && null !== $job->getId();
    }

    private function updateOperation(AsyncOperation $operation, Job $job): bool
    {
        $hasChanges = false;
        $jobId = $job->getId();
        if ($operation->getJobId() !== $jobId) {
            $hasChanges = true;
            $operation->setJobId($jobId);
        }
        $progress = $job->getJobProgress();
        if ($progress >= 0 && $operation->getProgress() !== $progress) {
            $hasChanges = true;
            $operation->setProgress($progress);
        }
        $status = $this->getOperationStatus($job->getStatus());
        if ($status && $operation->getStatus() !== $status) {
            $hasChanges = true;
            $operation->setStatus($status);
            if (AsyncOperation::STATUS_SUCCESS === $status) {
                $operation->setProgress(1);
                $summary = $this->getTotalSummary($job, $operation);
                $operation->setSummary($summary);
                $operation->setHasErrors(($summary[self::ERROR_COUNT] ?? 0) > 0);
            } elseif (AsyncOperation::STATUS_FAILED === $status) {
                $operation->setSummary($this->getTotalSummary($job, $operation));
                $operation->setHasErrors(true);
            }
        }

        return $hasChanges;
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
