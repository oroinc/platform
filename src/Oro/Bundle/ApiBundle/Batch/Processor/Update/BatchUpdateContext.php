<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Model\BatchSummary;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;
use Oro\Bundle\ApiBundle\Processor\ByStepNormalizeResultContext;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\ChainProcessor\ParameterBag;
use Oro\Component\ChainProcessor\ParameterBagInterface;

/**
 * The context for the "batch_update" action.
 */
class BatchUpdateContext extends ByStepNormalizeResultContext
{
    private ?int $operationId = null;
    private BatchSummary $summary;
    private bool $hasUnexpectedErrors = false;
    private ?string $retryReason = null;
    private ?FileManager $fileManager = null;
    private ?ChunkFile $file = null;
    /** @var string[] */
    private array $supportedEntityClasses = [];
    private ?IncludedData $includedData = null;
    /** @var BatchUpdateItem[]|null */
    private ?array $batchItems = null;
    /** @var int[]|null */
    private ?array $processedItemStatuses = null;
    private ?BatchFlushDataHandlerInterface $flushDataHandler = null;
    private ?ParameterBagInterface $sharedData = null;

    /**
     * {@inheritDoc}
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->summary = new BatchSummary();
    }

    /**
     * Gets an identifier of an asynchronous operation a batch operation is processed within.
     */
    public function getOperationId(): int
    {
        return $this->operationId;
    }

    /**
     * Sets an identifier of an asynchronous operation a batch operation is processed within.
     */
    public function setOperationId(int $operationId): void
    {
        $this->operationId = $operationId;
    }

    /**
     * Gets the summary statistics of this batch operation.
     */
    public function getSummary(): BatchSummary
    {
        return $this->summary;
    }

    /**
     * Indicates whether some unexpected errors occurred when processing this batch operation.
     */
    public function hasUnexpectedErrors(): bool
    {
        return $this->hasUnexpectedErrors;
    }

    /**
     * Sets a value indicates whether some unexpected errors occurred when processing this batch operation.
     */
    public function setHasUnexpectedErrors(bool $hasUnexpectedErrors): void
    {
        $this->hasUnexpectedErrors = $hasUnexpectedErrors;
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

    /**
     * Sets a reason why this batch operation cannot be processed now and should be processed again.
     */
    public function setRetryReason(?string $reason): void
    {
        $this->retryReason = $reason;
    }

    /**
     * Gets the file manager.
     */
    public function getFileManager(): FileManager
    {
        return $this->fileManager;
    }

    /**
     * Sets the file manager.
     */
    public function setFileManager(FileManager $fileManager): void
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Gets the file contains the request data.
     */
    public function getFile(): ChunkFile
    {
        return $this->file;
    }

    /**
     * Sets the file contains the request data.
     */
    public function setFile(ChunkFile $file): void
    {
        $this->file = $file;
    }

    /**
     * Gets entity classes supported by this batch operation.
     *
     * @return string[] The list of supported entity classes.
     *                  or empty array if any entities can be processed by this batch operation.
     */
    public function getSupportedEntityClasses(): array
    {
        return $this->supportedEntityClasses;
    }

    /**
     * Sets entity classes supported by this batch operation.
     *
     * @param string[] $supportedEntityClasses
     */
    public function setSupportedEntityClasses(array $supportedEntityClasses): void
    {
        $this->supportedEntityClasses = $supportedEntityClasses;
    }

    /**
     * Gets included data.
     */
    public function getIncludedData(): ?IncludedData
    {
        return $this->includedData;
    }

    /**
     * Sets included data.
     */
    public function setIncludedData(?IncludedData $includedData): void
    {
        $this->includedData = $includedData;
    }

    /**
     * Gets items that should be processed by this batch operation.
     *
     * @return BatchUpdateItem[]|null
     */
    public function getBatchItems(): ?array
    {
        return $this->batchItems;
    }

    /**
     * Sets items that should be processed by this batch operation.
     *
     * @param BatchUpdateItem[] $items
     */
    public function setBatchItems(array $items): void
    {
        $this->batchItems = $items;
    }

    /**
     * Removed batch items from the context.
     */
    public function clearBatchItems(): void
    {
        $this->batchItems = null;
    }

    /**
     * Gets items were processed by this batch operation without any errors.
     *
     * @return iterable<BatchUpdateItem>
     */
    public function getBatchItemsProcessedWithoutErrors(): iterable
    {
        if ($this->batchItems) {
            foreach ($this->batchItems as $item) {
                if (BatchUpdateItemStatus::NO_ERRORS === ($this->processedItemStatuses[$item->getIndex()] ?? null)) {
                    yield $item;
                }
            }
        }
    }

    /**
     * Gets the statuses of a specific item processed by this batch operation.
     * @see \Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus
     */
    public function getProcessedItemStatus(BatchUpdateItem $item): ?int
    {
        return $this->processedItemStatuses[$item->getIndex()] ?? null;
    }

    /**
     * Gets statuses of items processed by this batch operation.
     * @see \Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus
     *
     * @return int[]|null [batch item index => status, ...]
     */
    public function getProcessedItemStatuses(): ?array
    {
        return $this->processedItemStatuses;
    }

    /**
     * Sets statuses of items processed by this batch operation.
     * @see \Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus
     *
     * @param int[]|null $statuses [batch item index => status, ...]
     */
    public function setProcessedItemStatuses(?array $statuses): void
    {
        $this->processedItemStatuses = $statuses;
    }

    /**
     * Gets a handler that was used to flush data in this batch operation.
     */
    public function getFlushDataHandler(): ?BatchFlushDataHandlerInterface
    {
        return $this->flushDataHandler;
    }

    /**
     * Sets a handler that was used to flush data in this batch operation.
     */
    public function setFlushDataHandler(?BatchFlushDataHandlerInterface $flushDataHandler): void
    {
        $this->flushDataHandler = $flushDataHandler;
    }

    /**
     * Gets an object that is used to share data between a primary action
     * and actions that are executed as part of this action.
     * Also, this object can be used to share data between different kind of child actions.
     */
    public function getSharedData(): ParameterBagInterface
    {
        if (null === $this->sharedData) {
            $this->sharedData = new ParameterBag();
        }

        return $this->sharedData;
    }

    /**
     * Sets an object that is used to share data between a primary action
     * and actions that are executed as part of this action.
     * Also, this object can be used to share data between different kind of child actions.
     */
    public function setSharedData(ParameterBagInterface $sharedData): void
    {
        $this->sharedData = $sharedData;
    }
}
