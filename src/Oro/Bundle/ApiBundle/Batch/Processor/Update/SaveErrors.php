<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\ErrorManager;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Batch\RetryHelper;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * Checks if there are any errors in the context or contexts of batch items,
 * and if so, saves all errors to the persistent storage.
 */
class SaveErrors implements ProcessorInterface
{
    private ErrorManager $errorManager;
    private RetryHelper $retryHelper;
    private LoggerInterface $logger;

    public function __construct(ErrorManager $errorManager, RetryHelper $retryHelper, LoggerInterface $logger)
    {
        $this->errorManager = $errorManager;
        $this->retryHelper = $retryHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        $errors = $this->getErrors($context);
        if (!$errors) {
            return;
        }

        try {
            $this->errorManager->writeErrors(
                $context->getFileManager(),
                $context->getOperationId(),
                $errors,
                $context->getFile()
            );
            $context->setHasUnexpectedErrors($context->hasErrors());
        } catch (\Exception $e) {
            $context->setHasUnexpectedErrors(true);
            $this->logger->error(
                'Failed to save errors occurred when processing a batch operation chunk.',
                [
                    'operationId' => $context->getOperationId(),
                    'chunkFile'   => $context->getFile()->getFileName(),
                    'exception'   => $e
                ]
            );
        }

        $context->resetErrors();
    }

    /**
     * @param BatchUpdateContext $context
     *
     * @return BatchError[]
     */
    private function getErrors(BatchUpdateContext $context): array
    {
        $result = [];
        $errors = $context->getErrors();
        foreach ($errors as $error) {
            $result[] = $this->createBatchError($error);
        }
        $items = $context->getBatchItems();
        if ($items) {
            $processedItemStatuses = $context->getProcessedItemStatuses() ?? [];
            $hasItemsToRetry = $this->retryHelper->hasItemsToRetry(
                $context->getResult() ?? [],
                $processedItemStatuses
            );
            foreach ($items as $item) {
                if ($this->retryHelper->hasItemErrorsToSave($item, $hasItemsToRetry, $processedItemStatuses)) {
                    $itemErrors = $item->getContext()->getErrors();
                    foreach ($itemErrors as $itemError) {
                        $itemBatchError = $this->createBatchError($itemError);
                        $itemBatchError->setItemIndex($item->getIndex());
                        $result[] = $itemBatchError;
                    }
                }
            }
        }

        return $result;
    }

    private function createBatchError(Error $error): BatchError
    {
        $result = new BatchError();
        $result->setStatusCode($error->getStatusCode());
        $result->setCode($error->getCode());
        $result->setTitle($error->getTitle());
        $result->setDetail($error->getDetail());
        $result->setInnerException($error->getInnerException());
        $result->setSource($error->getSource());

        return $result;
    }
}
