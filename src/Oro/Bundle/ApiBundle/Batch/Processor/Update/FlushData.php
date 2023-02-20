<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandlerFactoryRegistry;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * Flushes entities stored in the context into a storage, e.g. the database.
 */
class FlushData implements ProcessorInterface
{
    public const OPERATION_NAME = 'flush_data';

    private const NO_ERRORS = 0;
    private const HAS_ERRORS = 1;
    private const FLUSH_EXCEPTION = 2;

    private BatchFlushDataHandlerFactoryRegistry $flushDataHandlerFactoryRegistry;
    private LoggerInterface $logger;

    public function __construct(
        BatchFlushDataHandlerFactoryRegistry $flushDataHandlerFactoryRegistry,
        LoggerInterface $logger
    ) {
        $this->flushDataHandlerFactoryRegistry = $flushDataHandlerFactoryRegistry;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // data were already flushed
            return;
        }

        $items = $context->getBatchItems();
        if ($items) {
            $processedItemStatuses = $context->getProcessedItemStatuses();
            $itemsToProcess = $this->getItemsToProcess($items, $processedItemStatuses);
            if ($itemsToProcess) {
                try {
                    $entityClass = $this->getEntityClass($items);
                    if ($entityClass) {
                        $flushHandler = $this->getFlushDataHandler($entityClass);
                        $context->setFlushDataHandler($flushHandler);
                        $flushResult = $this->flushItems($itemsToProcess, $flushHandler, $context);
                    } else {
                        $flushResult = self::HAS_ERRORS;
                    }
                    foreach ($itemsToProcess as $item) {
                        if (self::NO_ERRORS === $flushResult) {
                            $processedItemStatuses[$item->getIndex()] = BatchUpdateItemStatus::NO_ERRORS;
                        } elseif (self::FLUSH_EXCEPTION === $flushResult) {
                            $processedItemStatuses[$item->getIndex()] = BatchUpdateItemStatus::HAS_ERRORS;
                        } elseif (self::HAS_ERRORS === $flushResult && $item->getContext()->hasErrors()) {
                            $processedItemStatuses[$item->getIndex()] = BatchUpdateItemStatus::HAS_PERMANENT_ERRORS;
                        }
                    }
                } finally {
                    $context->setProcessedItemStatuses($processedItemStatuses);
                }
            }
        }
        $context->setProcessed(self::OPERATION_NAME);
    }

    /**
     * @param BatchUpdateItem[] $items
     * @param int[]             $processedItemStatuses
     *
     * @return array
     */
    private function getItemsToProcess(array $items, array $processedItemStatuses): array
    {
        $itemsToProcess = [];
        foreach ($items as $item) {
            if (BatchUpdateItemStatus::NOT_PROCESSED === $processedItemStatuses[$item->getIndex()]) {
                $itemsToProcess[] = $item;
            }
        }

        return $itemsToProcess;
    }

    /**
     * @param BatchUpdateItem[]              $items
     * @param BatchFlushDataHandlerInterface $flushHandler
     * @param BatchUpdateContext             $context
     *
     * @return int
     */
    private function flushItems(
        array $items,
        BatchFlushDataHandlerInterface $flushHandler,
        BatchUpdateContext $context
    ): int {
        $flushResult = self::NO_ERRORS;
        $flushHandler->startFlushData($items);
        try {
            if ($this->hasItemsWithErrors($items)) {
                $flushResult = self::HAS_ERRORS;
            } else {
                $flushHandler->flushData($items);
            }
        } catch (\Throwable $e) {
            $flushResult = self::FLUSH_EXCEPTION;
            $isUniqueConstraintViolationException = ($e instanceof UniqueConstraintViolationException);
            if (!$isUniqueConstraintViolationException) {
                $this->logger->error(
                    'Unexpected error occurred when flushing data for a batch operation chunk.',
                    [
                        'operationId' => $context->getOperationId(),
                        'chunkFile'   => $context->getFile()->getFileName(),
                        'exception'   => $e
                    ]
                );
            }
            if (\count($items) === 1) {
                $item = reset($items);
                if ($isUniqueConstraintViolationException) {
                    $item->getContext()->addError(
                        Error::createConflictValidationError('The entity already exists')
                            ->setInnerException($e)
                    );
                } else {
                    if ($e instanceof \Error) {
                        $e = new \ErrorException(
                            $e->getMessage(),
                            $e->getCode(),
                            E_ERROR,
                            $e->getFile(),
                            $e->getLine()
                        );
                    }
                    $item->getContext()->addError(Error::createByException($e));
                }
            }
        } finally {
            $flushHandler->finishFlushData($items);
        }

        return $flushResult;
    }

    /**
     * @param BatchUpdateItem[] $items
     *
     * @return bool
     */
    private function hasItemsWithErrors(array $items): bool
    {
        foreach ($items as $item) {
            if ($item->getContext()->hasErrors()) {
                return true;
            }
        }

        return false;
    }

    private function getFlushDataHandler(string $entityClass): BatchFlushDataHandlerInterface
    {
        $flushHandler = $this->flushDataHandlerFactoryRegistry->getFactory($entityClass)->createHandler($entityClass);
        if (null === $flushHandler) {
            throw new \LogicException(sprintf('The flush data handler is not registered for %s.', $entityClass));
        }

        return $flushHandler;
    }

    /**
     * @param BatchUpdateItem[] $items
     *
     * @return string|null
     */
    private function getEntityClass(array $items): ?string
    {
        foreach ($items as $item) {
            $itemContext = $item->getContext();
            if (!$itemContext->hasErrors()) {
                return $itemContext->getClassName();
            }
        }

        return null;
    }
}
