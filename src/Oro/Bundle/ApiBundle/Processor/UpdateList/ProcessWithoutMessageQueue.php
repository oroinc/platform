<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Batch\Async\AsyncOperationManager;
use Oro\Bundle\ApiBundle\Batch\Async\ChunkFileClassifierRegistry;
use Oro\Bundle\ApiBundle\Batch\Async\UpdateListProcessingHelper;
use Oro\Bundle\ApiBundle\Batch\Encoder\DataEncoderRegistry;
use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateHandler;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateRequest;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateResponse;
use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\IncludeAccessorRegistry;
use Oro\Bundle\ApiBundle\Batch\IncludeMapManager;
use Oro\Bundle\ApiBundle\Batch\Model\BatchAffectedEntitiesMerger;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Batch\Model\BatchSummary;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Batch\RetryHelper;
use Oro\Bundle\ApiBundle\Batch\Splitter\FileSplitterInterface;
use Oro\Bundle\ApiBundle\Batch\Splitter\FileSplitterRegistry;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Exception\FileSplitterException;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Processes Batch API operation without using of the message queue.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ProcessWithoutMessageQueue implements ProcessorInterface
{
    private const AGGREGATE_TIME = 'aggregateTime';
    private const READ_COUNT = 'readCount';
    private const WRITE_COUNT = 'writeCount';
    private const ERROR_COUNT = 'errorCount';
    private const CREATE_COUNT = 'createCount';
    private const UPDATE_COUNT = 'updateCount';

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly BatchUpdateHandler $handler,
        private readonly DoctrineHelper $doctrineHelper,
        private readonly FileSplitterRegistry $splitterRegistry,
        private readonly ChunkFileClassifierRegistry $chunkFileClassifierRegistry,
        private readonly IncludeAccessorRegistry $includeAccessorRegistry,
        private readonly IncludeMapManager $includeMapManager,
        private readonly FileManager $sourceDataFileManager,
        private readonly FileManager $fileManager,
        private readonly AsyncOperationManager $operationManager,
        private readonly UpdateListProcessingHelper $processingHelper,
        private readonly FileNameProvider $fileNameProvider,
        private readonly RetryHelper $retryHelper,
        private readonly DataEncoderRegistry $dataEncoderRegistry
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        $operationId = $context->getOperationId();
        if (null === $operationId) {
            return;
        }

        $dataFileName = $context->getTargetFileName();
        if (null === $dataFileName) {
            return;
        }

        $startTimestamp = microtime(true);
        $chunkFile = $this->prepareChunkFile(
            $operationId,
            $context->getRequestType(),
            $dataFileName
        );
        if (null !== $chunkFile) {
            $this->processChunkFile(
                $startTimestamp,
                $chunkFile,
                $operationId,
                $context->getVersion(),
                $context->getRequestType(),
                $context->getClassName(),
                $dataFileName
            );
        }
    }

    private function prepareChunkFile(int $operationId, RequestType $requestType, string $dataFileName): ?ChunkFile
    {
        [$chunkFile, $includedChunkFile] = $this->splitToChunkFiles($operationId, $requestType, $dataFileName);
        if (null === $chunkFile) {
            return null;
        }

        if (null !== $includedChunkFile) {
            $includeAccessor = $this->includeAccessorRegistry->getAccessor($requestType);
            if (null === $includeAccessor) {
                $this->operationManager->markAsFailed(
                    $operationId,
                    $dataFileName,
                    'An include accessor was not found.'
                );

                return null;
            }
            $errors = $this->includeMapManager->updateIncludedChunkIndex(
                $this->fileManager,
                $operationId,
                $includeAccessor,
                [$includedChunkFile]
            );
            if ($errors) {
                $errorsToSave = [];
                foreach ($errors as [$sectionName, $itemIndex, $errorMessage]) {
                    $errorsToSave[] = BatchError::createValidationError(Constraint::REQUEST_DATA, $errorMessage)
                        ->setSource(ErrorSource::createByPointer(\sprintf('/%s/%s', $sectionName, $itemIndex)));
                }
                $this->operationManager->addErrors($operationId, $dataFileName, $errorsToSave);
            }
        }

        $this->processingHelper->updateChunkIndex($operationId, [$chunkFile]);

        return $chunkFile;
    }

    private function splitToChunkFiles(int $operationId, RequestType $requestType, string $dataFileName): array
    {
        $splitter = $this->splitterRegistry->getSplitter($requestType);
        if (null === $splitter) {
            $this->operationManager->markAsFailed(
                $operationId,
                $dataFileName,
                'A file splitter was not found.'
            );

            return [null, null];
        }

        $classifier = $this->chunkFileClassifierRegistry->getClassifier($requestType);
        if (null === $classifier) {
            $this->operationManager->markAsFailed(
                $operationId,
                $dataFileName,
                'A chunk file classifier was not found.'
            );

            return [null, null];
        }

        $files = $this->splitDataFile($splitter, $operationId, $dataFileName);
        if (null === $files) {
            return [null, null];
        }

        $chunkFile = null;
        $includedChunkFile = null;
        foreach ($files as $file) {
            if ($classifier->isPrimaryData($file)) {
                $chunkFile = $file;
            } elseif ($classifier->isIncludedData($file)) {
                $includedChunkFile = $file;
            }
        }

        return [$chunkFile, $includedChunkFile];
    }

    /**
     * @return ChunkFile[]|null
     */
    private function splitDataFile(FileSplitterInterface $splitter, int $operationId, string $dataFileName): ?array
    {
        $splitter->setChunkFileNameTemplate($this->fileNameProvider->getChunkFileNameTemplate($operationId));

        $initialChunkSize = $splitter->getChunkSize();
        $initialChunkSizePerSection = $splitter->getChunkSizePerSection();
        $initialChunkCountLimit = $splitter->getChunkCountLimit();
        $initialChunkCountLimitPerSection = $splitter->getChunkCountLimitPerSection();

        $splitter->setChunkSize(\PHP_INT_MAX);
        $chunkSizePerSection = [];
        foreach ($initialChunkSizePerSection as $sectionName => $chunkSize) {
            $chunkSizePerSection[$sectionName] = \PHP_INT_MAX;
        }
        $splitter->setChunkSizePerSection($chunkSizePerSection);
        $splitter->setChunkCountLimit(1);
        $chunkCountLimitPerSection = [];
        foreach ($initialChunkSizePerSection as $sectionName => $chunkSize) {
            $chunkCountLimitPerSection[$sectionName] = 1;
        }
        $splitter->setChunkCountLimitPerSection($chunkCountLimitPerSection);
        try {
            return $splitter->splitFile($dataFileName, $this->sourceDataFileManager, $this->fileManager);
        } catch (\Exception $exception) {
            $errorMessage = $exception->getMessage();
            if ($exception instanceof FileSplitterException) {
                $this->processingHelper->safeDeleteFilesAfterFileSplitterFailure($exception, $operationId);
                $errorMessage = $this->processingHelper->getFileSplitterFailureErrorMessage($exception);
            }
            $this->operationManager->markAsFailed($operationId, $dataFileName, $errorMessage);

            return null;
        } finally {
            $splitter->setChunkSize($initialChunkSize);
            $splitter->setChunkSizePerSection($initialChunkSizePerSection);
            $splitter->setChunkCountLimit($initialChunkCountLimit);
            $splitter->setChunkCountLimitPerSection($initialChunkCountLimitPerSection);
        }
    }

    private function processChunkFile(
        float $startTimestamp,
        ChunkFile $chunkFile,
        int $operationId,
        string $version,
        RequestType $requestType,
        string $entityClass,
        string $dataFileName,
        int $chunkCount = 1
    ): void {
        $request = new BatchUpdateRequest(
            $version,
            $requestType,
            $operationId,
            [$entityClass],
            $chunkFile,
            $this->fileManager
        );

        $response = $this->handler->handle($request);

        $operation = $this->doctrineHelper->createQueryBuilder(AsyncOperation::class, 'o')
            ->where('o.id = :id')
            ->setParameter('id', $operationId)
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
        if (null !== $operation) {
            $this->operationManager->updateOperation($operationId, function () use (
                $operation,
                $response,
                $startTimestamp
            ) {
                return $this->updateOperation($operation, $response, $startTimestamp);
            });
        }

        if ($response->isRetryAgain()) {
            $this->operationManager->addErrors($operationId, $dataFileName, [
                BatchError::create('runtime exception', $response->getRetryReason())
                    ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
            ]);
        } elseif (!$response->hasUnexpectedErrors()) {
            $rawItems = $response->getData();
            $processedItemStatuses = $response->getProcessedItemStatuses();
            if ($rawItems
                && $processedItemStatuses
                && $this->retryHelper->hasItemsToRetry($rawItems, $processedItemStatuses)
            ) {
                $chunksToRetry = $this->retryHelper->getChunksToRetry($rawItems, $processedItemStatuses);
                if ($chunksToRetry) {
                    $this->processRetryChunks(
                        $startTimestamp,
                        $chunksToRetry,
                        $chunkFile,
                        $operationId,
                        $version,
                        $requestType,
                        $entityClass,
                        $dataFileName,
                        $chunkCount
                    );
                }
            }
        }
    }

    private function processRetryChunks(
        float $startTimestamp,
        array $chunksToRetry,
        ChunkFile $parentChunkFile,
        int $operationId,
        string $version,
        RequestType $requestType,
        string $entityClass,
        string $dataFileName,
        int $chunkCount
    ): void {
        $dataEncoder = $this->dataEncoderRegistry->getEncoder($requestType);
        if (null === $dataEncoder) {
            return;
        }

        $numberOfChunksToRetry = \count($chunksToRetry);
        $chunkCount += $numberOfChunksToRetry;
        $chunkFiles = $this->processingHelper->getChunkFilesToRetry(
            $parentChunkFile,
            $chunksToRetry,
            $chunkCount - $numberOfChunksToRetry,
            $dataEncoder
        );
        foreach ($chunkFiles as $chunkFile) {
            $this->processChunkFile(
                $startTimestamp,
                $chunkFile,
                $operationId,
                $version,
                $requestType,
                $entityClass,
                $dataFileName,
                $chunkCount
            );
        }
    }

    private function updateOperation(
        AsyncOperation $operation,
        BatchUpdateResponse $response,
        float $startTimestamp
    ): array {
        $hasErrors = AsyncOperation::STATUS_FAILED === $operation->getStatus()
            || $response->hasUnexpectedErrors()
            || $response->isRetryAgain()
            || $this->hasFailedItems($response->getProcessedItemStatuses());
        $summary = $this->getOperationSummary($operation, $response->getSummary(), $startTimestamp);
        $data = [
            'progress' => $hasErrors ? 0 : 1,
            'status' => $hasErrors ? AsyncOperation::STATUS_FAILED : AsyncOperation::STATUS_SUCCESS,
            'summary' => $summary,
            'hasErrors' => $hasErrors || $summary[self::ERROR_COUNT] > 0
        ];
        $affectedEntities = $response->getAffectedEntities()->toArray();
        if ($affectedEntities) {
            $operationAffectedEntities = $operation->getAffectedEntities();
            if ($operationAffectedEntities) {
                $affectedEntities = self::mergeAffectedEntities($operationAffectedEntities, $affectedEntities);
            }
            $data['affectedEntities'] = $affectedEntities;
        }

        return $data;
    }

    private function hasFailedItems(array $processedItemStatuses): bool
    {
        foreach ($processedItemStatuses as $status) {
            if (BatchUpdateItemStatus::HAS_PERMANENT_ERRORS === $status
                || BatchUpdateItemStatus::NOT_PROCESSED === $status
            ) {
                return true;
            }
        }

        return false;
    }

    private function getOperationSummary(
        AsyncOperation $operation,
        BatchSummary $summary,
        float $startTimestamp
    ): array {
        $totalSummary = array_merge([
            self::AGGREGATE_TIME => 0,
            self::READ_COUNT => 0,
            self::WRITE_COUNT => 0,
            self::ERROR_COUNT => 0,
            self::CREATE_COUNT => 0,
            self::UPDATE_COUNT => 0
        ], $operation->getSummary() ?? []);
        $totalSummary[self::AGGREGATE_TIME] = $this->processingHelper->calculateAggregateTime(
            $startTimestamp,
            $totalSummary[self::AGGREGATE_TIME]
        );
        if (0 === $totalSummary[self::READ_COUNT]) {
            $totalSummary[self::READ_COUNT] = $summary->getReadCount();
        }
        $totalSummary[self::WRITE_COUNT] += $summary->getWriteCount();
        $totalSummary[self::ERROR_COUNT] += $summary->getErrorCount();
        $totalSummary[self::CREATE_COUNT] += $summary->getCreateCount();
        $totalSummary[self::UPDATE_COUNT] += $summary->getUpdateCount();

        return $totalSummary;
    }

    private static function mergeAffectedEntities(array $affectedEntities, array $toMerge): array
    {
        BatchAffectedEntitiesMerger::mergeAffectedEntities($affectedEntities, $toMerge);

        return $affectedEntities;
    }
}
