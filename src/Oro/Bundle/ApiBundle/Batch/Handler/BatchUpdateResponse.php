<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

use Oro\Bundle\ApiBundle\Batch\Model\BatchAffectedEntities;
use Oro\Bundle\ApiBundle\Batch\Model\BatchSummary;

/**
 * Represents the response of API batch update operation.
 */
class BatchUpdateResponse
{
    private array $data;
    /** @var int[] */
    private array $processedItemStatuses;
    private BatchSummary $summary;
    private BatchAffectedEntities $affectedEntities;
    private bool $hasUnexpectedErrors;
    private ?string $retryReason;

    /**
     * @param array                 $data
     * @param int[]                 $processedItemStatuses
     * @param BatchSummary          $summary
     * @param BatchAffectedEntities $affectedEntities
     * @param bool                  $hasUnexpectedErrors
     * @param string|null           $retryReason
     */
    public function __construct(
        array $data,
        array $processedItemStatuses,
        BatchSummary $summary,
        BatchAffectedEntities $affectedEntities,
        bool $hasUnexpectedErrors,
        ?string $retryReason = null
    ) {
        $this->data = $data;
        $this->processedItemStatuses = $processedItemStatuses;
        $this->summary = $summary;
        $this->affectedEntities = $affectedEntities;
        $this->hasUnexpectedErrors = $hasUnexpectedErrors;
        $this->retryReason = $retryReason;
    }

    /**
     * Gets data items that are processed by this batch operation.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Gets statuses of processed batch items.
     * @see \Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus
     *
     * @return int[] [batch item index => status, ...]
     */
    public function getProcessedItemStatuses(): array
    {
        return $this->processedItemStatuses;
    }

    /**
     * Gets the summary statistics of this batch operation.
     */
    public function getSummary(): BatchSummary
    {
        return $this->summary;
    }

    /**
     * Gets entities affected by this batch operation.
     */
    public function getAffectedEntities(): BatchAffectedEntities
    {
        return $this->affectedEntities;
    }

    /**
     * Indicates whether some unexpected errors occurred when processing this batch operation.
     */
    public function hasUnexpectedErrors(): bool
    {
        return $this->hasUnexpectedErrors;
    }

    /**
     * Indicates whether this batch operation cannot be processed now and it is required to retry it.
     */
    public function isRetryAgain(): bool
    {
        return null !== $this->retryReason;
    }

    /**
     * Gets a reason why this batch operation cannot be processed now and should be processed again.
     */
    public function getRetryReason(): ?string
    {
        return $this->retryReason;
    }
}
