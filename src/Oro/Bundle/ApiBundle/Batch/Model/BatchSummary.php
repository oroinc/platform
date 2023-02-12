<?php

namespace Oro\Bundle\ApiBundle\Batch\Model;

/**
 * Represents the summary of a batch operation.
 */
final class BatchSummary
{
    private int $readCount = 0;
    private int $writeCount = 0;
    private int $errorCount = 0;
    private int $createCount = 0;
    private int $updateCount = 0;

    /**
     * Gets the number of items that have been successfully read.
     */
    public function getReadCount(): int
    {
        return $this->readCount;
    }

    /**
     * Increments the number of items that have been successfully read.
     */
    public function incrementReadCount(int $increment = 1): void
    {
        $this->readCount += $increment;
    }

    /**
     * Gets the number of items that have been successfully written.
     */
    public function getWriteCount(): int
    {
        return $this->writeCount;
    }

    /**
     * Increments the number of items that have been successfully written.
     */
    public function incrementWriteCount(int $increment = 1): void
    {
        $this->writeCount += $increment;
    }

    /**
     * Gets the number of errors occurred when processing this batch operation.
     */
    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    /**
     * Increments the number of errors occurred when processing this batch operation.
     */
    public function incrementErrorCount(int $increment = 1): void
    {
        $this->errorCount += $increment;
    }

    /**
     * Gets the number of items that have been successfully created.
     */
    public function getCreateCount(): int
    {
        return $this->createCount;
    }

    /**
     * Increments the number of items that have been successfully created.
     */
    public function incrementCreateCount(int $increment = 1): void
    {
        $this->createCount += $increment;
    }

    /**
     * Gets the number of items that have been successfully updated.
     */
    public function getUpdateCount(): int
    {
        return $this->updateCount;
    }

    /**
     * Increments the number of items that have been successfully updated.
     */
    public function incrementUpdateCount(int $increment = 1): void
    {
        $this->updateCount += $increment;
    }
}
