<?php

namespace Oro\Bundle\ImportExportBundle\Writer\ComplexData;

use Oro\Bundle\ApiBundle\Async\Topic\DeleteAsyncOperationTopic;
use Oro\Bundle\ApiBundle\Batch\ErrorManager;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\UpdateList\UpdateListContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\BatchApiToImportErrorConverterInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * The import writer that uses Batch API in JSON:API format
 * to validate and write data into a storage, e.g. the database.
 */
class JsonApiBatchApiImportWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    private StepExecution $stepExecution;

    public function __construct(
        private readonly ContextRegistry $contextRegistry,
        private readonly ActionProcessorBagInterface $actionProcessorBag,
        private readonly FileManager $fileManager,
        private readonly ErrorManager $errorManager,
        private readonly BatchApiToImportErrorConverterInterface $errorConverter,
        private readonly MessageProducerInterface $producer,
        private readonly string $entityClass,
        private readonly string $requestType
    ) {
    }

    #[\Override]
    public function setStepExecution(StepExecution $stepExecution): void
    {
        $this->stepExecution = $stepExecution;
    }

    #[\Override]
    public function write(array $items): void
    {
        $requestData = [];
        $requestDataToProcess = [];
        $requestDataErrors = [];
        $itemIndexMap = [];
        foreach ($items as $itemIndex => $item) {
            $requestData[JsonApiDoc::DATA][] = $item[JsonApiDoc::DATA];
            if (!empty($item[JsonApiDoc::INCLUDED])) {
                foreach ($item[JsonApiDoc::INCLUDED] as $includedItem) {
                    $requestData[JsonApiDoc::INCLUDED][] = $includedItem;
                }
            }
            if (empty($item[JsonApiDoc::ERRORS])) {
                $itemIndexMap[\count($requestDataToProcess[JsonApiDoc::DATA] ?? [])] = $itemIndex;
                $requestDataToProcess[JsonApiDoc::DATA][] = $item[JsonApiDoc::DATA];
                if (!empty($item[JsonApiDoc::INCLUDED])) {
                    foreach ($item[JsonApiDoc::INCLUDED] as $includedItem) {
                        $requestDataToProcess[JsonApiDoc::INCLUDED][] = $includedItem;
                    }
                }
            } else {
                $requestDataErrors[$itemIndex] = $item[JsonApiDoc::ERRORS];
            }
        }

        if (!$requestDataToProcess) {
            $context = $this->contextRegistry->getByStepExecution($this->stepExecution);
            $errorCount = $this->addErrorsToImportContext(
                $context,
                null,
                $requestData,
                $requestDataToProcess,
                $itemIndexMap,
                $requestDataErrors
            );
            $context->incrementErrorEntriesCount($errorCount);

            return;
        }

        $apiProcessor = $this->actionProcessorBag->getProcessor(ApiAction::UPDATE_LIST);
        /** @var UpdateListContext $apiContext */
        $apiContext = $apiProcessor->createContext();
        $apiContext->getRequestType()->add(RequestType::REST);
        $apiContext->getRequestType()->add(RequestType::JSON_API);
        $apiContext->getRequestType()->add($this->requestType);
        $apiContext->setMainRequest(true);
        $apiContext->setClassName($this->entityClass);
        $apiContext->setRequestData($requestDataToProcess);
        $apiContext->setProcessByMessageQueue(false);

        $apiProcessor->process($apiContext);

        $apiResult = $apiContext->getResult();
        $operationId = $apiContext->getOperationId();
        if (null === $operationId) {
            $this->processErrorResult($apiResult);
        } else {
            try {
                $this->processResult(
                    $apiResult,
                    $operationId,
                    $requestData,
                    $requestDataToProcess,
                    $itemIndexMap,
                    $requestDataErrors
                );
            } finally {
                $this->producer->send(DeleteAsyncOperationTopic::getName(), ['operationId' => $operationId]);
            }
        }
    }

    private function processResult(
        mixed $result,
        int $operationId,
        array $requestData,
        array $requestDataToProcess,
        array $itemIndexMap,
        array $requestDataErrors
    ): void {
        if (!\is_array($result)) {
            return;
        }

        $resultData = $result[JsonApiDoc::DATA];
        $status = $resultData[JsonApiDoc::ATTRIBUTES]['status'];
        if (AsyncOperation::STATUS_SUCCESS === $status && !$requestDataErrors) {
            $context = $this->contextRegistry->getByStepExecution($this->stepExecution);
            $summary = $resultData[JsonApiDoc::ATTRIBUTES]['summary'];
            $context->incrementAddCount($summary['createCount'] ?? 0);
            $context->incrementUpdateCount($summary['updateCount'] ?? 0);
        } elseif (AsyncOperation::STATUS_FAILED === $status || $requestDataErrors) {
            $context = $this->contextRegistry->getByStepExecution($this->stepExecution);
            $summary = $resultData[JsonApiDoc::ATTRIBUTES]['summary'];
            $context->incrementAddCount($summary['createCount'] ?? 0);
            $context->incrementUpdateCount($summary['updateCount'] ?? 0);
            $errorCount = $this->addErrorsToImportContext(
                $context,
                $operationId,
                $requestData,
                $requestDataToProcess,
                $itemIndexMap,
                $requestDataErrors
            );
            $context->incrementErrorEntriesCount($errorCount);
        }
    }

    private function processErrorResult(mixed $result): void
    {
        $errorMessage = 'The import failed.';
        if (\is_array($result) && !empty($result[JsonApiDoc::ERRORS])) {
            $errorMessage .= ' Reason:';
            foreach ($result[JsonApiDoc::ERRORS] as $error) {
                $reason = !empty($error[JsonApiDoc::ERROR_DETAIL])
                    ? $error[JsonApiDoc::ERROR_DETAIL]
                    : $error[JsonApiDoc::ERROR_TITLE];
                if (!str_ends_with($reason, '.')) {
                    $reason .= '.';
                }
                $errorMessage .= ' ' . $reason;
            }
        }
        $this->stepExecution->addError($errorMessage);
    }

    private function addErrorsToImportContext(
        ContextInterface $context,
        ?int $operationId,
        array $requestData,
        array $requestDataToProcess,
        array $itemIndexMap,
        array $requestDataErrors
    ): int {
        $importErrors = $this->getImportErrors(
            $operationId,
            $requestData,
            $requestDataToProcess,
            $itemIndexMap,
            $requestDataErrors
        );
        foreach ($importErrors as $importError) {
            $context->addError($importError);
        }

        return \count($importErrors);
    }

    private function getImportErrors(
        ?int $operationId,
        array $requestData,
        array $requestDataToProcess,
        array $itemIndexMap,
        array $requestDataErrors
    ): array {
        $groupedImportErrors = [];
        foreach ($requestDataErrors as $itemIndex => $itemErrors) {
            foreach ($itemErrors as $itemError) {
                $error = BatchError::create($itemError[JsonApiDoc::ERROR_TITLE], $itemError[JsonApiDoc::ERROR_DETAIL]);
                $errorPointer = $itemError[JsonApiDoc::ERROR_SOURCE][JsonApiDoc::ERROR_POINTER] ?? null;
                if ($errorPointer && str_starts_with($errorPointer, '/' . JsonApiDoc::DATA)) {
                    $errorPointer = \sprintf('/%s/%d', JsonApiDoc::DATA, $itemIndex)
                        . substr($errorPointer, \strlen(JsonApiDoc::DATA) + 1);
                    $error->setSource(ErrorSource::createByPointer($errorPointer));
                }
                $error->setItemIndex($itemIndex);
                $groupedImportErrors[$itemIndex][] = $this->errorConverter->convertToImportError(
                    $error,
                    $requestData
                );
            }
        }
        if (null !== $operationId) {
            $errors = $this->errorManager->readErrors($this->fileManager, $operationId, 0, \PHP_INT_MAX);
            foreach ($errors as $error) {
                $itemIndex = $itemIndexMap[$error->getItemIndex()] ?? $error->getItemIndex();
                $groupedImportErrors[$itemIndex][] = $this->errorConverter->convertToImportError(
                    $error,
                    $requestDataToProcess,
                    $itemIndex
                );
            }
        }

        if (!$groupedImportErrors) {
            return [];
        }

        ksort($groupedImportErrors);

        return array_unique(array_merge(...array_values($groupedImportErrors)));
    }
}
