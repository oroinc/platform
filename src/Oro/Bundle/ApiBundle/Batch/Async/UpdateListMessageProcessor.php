<?php

namespace Oro\Bundle\ApiBundle\Batch\Async;

use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListFinishTopic;
use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListTopic;
use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\IncludeAccessorRegistry;
use Oro\Bundle\ApiBundle\Batch\IncludeMapManager;
use Oro\Bundle\ApiBundle\Batch\JsonUtil;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Batch\Splitter\FileSplitterInterface;
use Oro\Bundle\ApiBundle\Batch\Splitter\FileSplitterRegistry;
use Oro\Bundle\ApiBundle\Batch\Splitter\PartialFileSplitterInterface;
use Oro\Bundle\ApiBundle\Exception\FileSplitterException;
use Oro\Bundle\ApiBundle\Exception\ParsingErrorFileSplitterException;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobManagerInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Splits data of API batch update request to chunks
 * and send a separate MQ message to process each chunk.
 */
class UpdateListMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private JobRunner $jobRunner;
    private JobManagerInterface $jobManager;
    private DependentJobService $dependentJob;
    private FileSplitterRegistry $splitterRegistry;
    private ChunkFileClassifierRegistry $chunkFileClassifierRegistry;
    private IncludeAccessorRegistry $includeAccessorRegistry;
    private IncludeMapManager $includeMapManager;
    private FileManager $sourceDataFileManager;
    private FileManager $fileManager;
    private MessageProducerInterface $producer;
    private AsyncOperationManager $operationManager;
    private UpdateListProcessingHelper $processingHelper;
    private FileNameProvider $fileNameProvider;
    private LoggerInterface $logger;
    private int $splitFileTimeout = 30000; // 30 seconds

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        JobRunner $jobRunner,
        JobManagerInterface $jobManager,
        DependentJobService $dependentJob,
        FileSplitterRegistry $splitterRegistry,
        ChunkFileClassifierRegistry $chunkFileClassifierRegistry,
        IncludeAccessorRegistry $includeAccessorRegistry,
        IncludeMapManager $includeMapManager,
        FileManager $sourceDataFileManager,
        FileManager $fileManager,
        MessageProducerInterface $producer,
        AsyncOperationManager $operationManager,
        UpdateListProcessingHelper $processingHelper,
        FileNameProvider $fileNameProvider,
        LoggerInterface $logger
    ) {
        $this->jobRunner = $jobRunner;
        $this->jobManager = $jobManager;
        $this->dependentJob = $dependentJob;
        $this->splitterRegistry = $splitterRegistry;
        $this->chunkFileClassifierRegistry = $chunkFileClassifierRegistry;
        $this->includeAccessorRegistry = $includeAccessorRegistry;
        $this->includeMapManager = $includeMapManager;
        $this->sourceDataFileManager = $sourceDataFileManager;
        $this->fileManager = $fileManager;
        $this->producer = $producer;
        $this->operationManager = $operationManager;
        $this->processingHelper = $processingHelper;
        $this->fileNameProvider = $fileNameProvider;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [UpdateListTopic::getName()];
    }

    /**
     * Sets the maximum number of milliseconds that the splitter can spend to split a file.
     *
     * @param int $milliseconds The timeout in milliseconds or -1 for unlimited
     */
    public function setSplitFileTimeout(int $milliseconds): void
    {
        $this->splitFileTimeout = $milliseconds;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $startTimestamp = microtime(true);
        $messageBody = $message->getBody();

        $operationId = $messageBody['operationId'];
        $splitterState = $this->getSplitterState($messageBody);
        if (!$splitterState) {
            // the first iteration - mark the asynchronous operation as running
            $this->operationManager->markAsRunning($operationId);
        }

        $dataFileName = $messageBody['fileName'];
        $requestType = new RequestType($messageBody['requestType']);

        $splitter = $this->splitterRegistry->getSplitter($requestType);
        if (null === $splitter) {
            $errorMessage = sprintf(
                'A file splitter was not found for the request type "%s".',
                (string)$requestType
            );
            $this->logger->error($errorMessage);
            $this->operationManager->markAsFailed($operationId, $dataFileName, $errorMessage);

            return self::REJECT;
        }

        $classifier = $this->chunkFileClassifierRegistry->getClassifier($requestType);
        if (null === $classifier) {
            $errorMessage = sprintf(
                'A chunk file classifier was not found for the request type "%s".',
                (string)$requestType
            );
            $this->logger->error($errorMessage);
            $this->operationManager->markAsFailed($operationId, $dataFileName, $errorMessage);

            return self::REJECT;
        }

        $files = $this->splitFile($splitter, $splitterState, $operationId, $dataFileName, $messageBody);
        if (null === $files) {
            return self::ACK;
        }

        $chunkFiles = [];
        $includedChunkFiles = [];
        foreach ($files as $file) {
            if ($classifier->isPrimaryData($file)) {
                $chunkFiles[] = $file;
            } elseif ($classifier->isIncludedData($file)) {
                $includedChunkFiles[] = $file;
            }
        }

        if ($includedChunkFiles) {
            $includeAccessor = $this->includeAccessorRegistry->getAccessor($requestType);
            if (null === $includeAccessor) {
                $errorMessage = sprintf(
                    'An include accessor was not found for the request type "%s".',
                    (string)$requestType
                );
                $this->logger->error($errorMessage);
                $this->operationManager->markAsFailed($operationId, $dataFileName, $errorMessage);

                return self::REJECT;
            }
            $errors = $this->includeMapManager->updateIncludedChunkIndex(
                $this->fileManager,
                $messageBody['operationId'],
                $includeAccessor,
                $includedChunkFiles
            );
            if ($errors) {
                $errorsToSave = [];
                foreach ($errors as [$sectionName, $itemIndex, $errorMessage]) {
                    $errorsToSave[] = BatchError::createValidationError(Constraint::REQUEST_DATA, $errorMessage)
                        ->setSource(ErrorSource::createByPointer(sprintf('/%s/%s', $sectionName, $itemIndex)));
                }
                $this->operationManager->addErrors($operationId, $dataFileName, $errorsToSave);
            }
        }
        $this->processChunkFiles(
            $splitter,
            $chunkFiles,
            $message,
            $messageBody,
            $startTimestamp
        );

        return self::ACK;
    }

    /**
     * @param FileSplitterInterface $splitter
     * @param ChunkFile[]           $chunkFiles
     * @param MessageInterface      $message
     * @param array                 $body
     * @param float                 $startTimestamp
     */
    private function processChunkFiles(
        FileSplitterInterface $splitter,
        array $chunkFiles,
        MessageInterface $message,
        array $body,
        float $startTimestamp
    ): void {
        $operationId = $body['operationId'];
        $previousSplitAggregateTime = $body['aggregateTime'];
        if ($splitter instanceof PartialFileSplitterInterface && !$splitter->isCompleted()) {
            // do the next iteration
            if ($chunkFiles) {
                $this->processingHelper->updateChunkIndex($operationId, $chunkFiles);
            }
            $splitAggregateTime = $this->processingHelper->calculateAggregateTime(
                $startTimestamp,
                $previousSplitAggregateTime
            );
            $this->operationManager->incrementAggregateTime($operationId, $splitAggregateTime);
            $this->processNextSplitIteration($body, $splitter->getState(), $splitAggregateTime);
        } else {
            // the splitting of the source file finished
            $splitAggregateTime = $this->processingHelper->calculateAggregateTime(
                $startTimestamp,
                $previousSplitAggregateTime
            );
            $startTimestamp = microtime(true);
            $delayedCreationOfChunkJobs = false;
            if ($this->processingHelper->hasChunkIndex($operationId)) {
                if ($chunkFiles) {
                    $this->processingHelper->updateChunkIndex($operationId, $chunkFiles);
                }
                $chunkFiles = $this->processingHelper->loadChunkIndex($operationId);
                $delayedCreationOfChunkJobs = true;
            }
            $this->processChunks($message, $body, $chunkFiles, $delayedCreationOfChunkJobs);
            $this->operationManager->incrementAggregateTime(
                $operationId,
                $this->processingHelper->calculateAggregateTime($startTimestamp, $splitAggregateTime)
            );
        }
    }

    private function processNextSplitIteration(array $body, array $splitterState, int $aggregateTime): void
    {
        $body['splitterState'] = $splitterState;
        $body['aggregateTime'] = $aggregateTime;
        $this->producer->send(UpdateListTopic::getName(), $body);
    }

    /**
     * @param MessageInterface $message
     * @param array       $body
     * @param ChunkFile[] $chunkFiles
     * @param bool        $delayed
     */
    private function processChunks(MessageInterface $message, array $body, array $chunkFiles, bool $delayed): void
    {
        $operationId = $body['operationId'];
        $dataFileName = $body['fileName'];

        $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner, Job $job) use ($operationId, $body, $chunkFiles, $delayed) {
                $chunkFileCount = \count($chunkFiles);
                $rootJob = $job->getRootJob();
                $this->saveOperationIdToJob($operationId, $rootJob);
                $this->createOperationInfoFile($operationId, $chunkFileCount);
                $this->createFinishJob($body, $rootJob);
                $chunkJobNameTemplate = $job->getName() . ':chunk:%s';
                if ($delayed) {
                    $this->sendMessageToCreateChunkJobs(
                        $jobRunner,
                        $job->getRootJob(),
                        $operationId,
                        $chunkJobNameTemplate,
                        $body
                    );
                } else {
                    $this->createChunkJobs($jobRunner, $chunkJobNameTemplate, $body, $chunkFiles);
                }

                return true;
            }
        );

        $this->safeDeleteDataFile($dataFileName);
    }

    /**
     * @param JobRunner   $jobRunner
     * @param string      $chunkJobNameTemplate
     * @param array       $parentBody
     * @param ChunkFile[] $chunkFiles
     */
    private function createChunkJobs(
        JobRunner $jobRunner,
        string $chunkJobNameTemplate,
        array $parentBody,
        array $chunkFiles
    ): void {
        foreach ($chunkFiles as $fileIndex => $chunkFile) {
            $jobRunner->createDelayed(
                sprintf($chunkJobNameTemplate, $fileIndex + 1),
                function (JobRunner $jobRunner, Job $job) use ($parentBody, $chunkFile) {
                    $this->processingHelper->sendProcessChunkMessage($parentBody, $job, $chunkFile);

                    return true;
                }
            );
        }
    }

    private function sendMessageToCreateChunkJobs(
        JobRunner $jobRunner,
        Job $rootJob,
        int $operationId,
        string $chunkJobNameTemplate,
        array $parentBody
    ): void {
        $nextChunkFileIndex = $this->processingHelper->createChunkJobs(
            $jobRunner,
            $operationId,
            $chunkJobNameTemplate,
            0,
            0
        );
        $this->processingHelper->sendMessageToCreateChunkJobs(
            $rootJob,
            $chunkJobNameTemplate,
            $parentBody,
            $nextChunkFileIndex
        );
    }

    private function saveOperationIdToJob(int $operationId, Job $rootJob): void
    {
        $data = $rootJob->getData();
        $data['api_operation_id'] = $operationId;
        $rootJob->setData($data);
        $this->jobManager->saveJob($rootJob);
    }

    private function createOperationInfoFile(int $operationId, int $chunkCount): void
    {
        $this->fileManager->writeToStorage(
            JsonUtil::encode(['chunkCount' => $chunkCount]),
            $this->fileNameProvider->getInfoFileName($operationId)
        );
    }

    private function createFinishJob(array $body, Job $rootJob): void
    {
        $context = $this->dependentJob->createDependentJobContext($rootJob);
        $context->addDependentJob(
            UpdateListFinishTopic::getName(),
            array_merge($this->processingHelper->getCommonBody($body), [
                'fileName' => $body['fileName']
            ])
        );
        $this->dependentJob->saveDependentJob($context);
    }

    private function getSplitterState(array $body): array
    {
        $splitterState = [];
        if (\array_key_exists('splitterState', $body)) {
            $splitterState = $body['splitterState'];
        }

        return $splitterState;
    }

    /**
     * @param FileSplitterInterface $splitter
     * @param array                 $splitterState
     * @param int                   $operationId
     * @param string                $dataFileName
     * @param array                 $body
     *
     * @return ChunkFile[]|null
     */
    private function splitFile(
        FileSplitterInterface $splitter,
        array $splitterState,
        int $operationId,
        string $dataFileName,
        array $body,
    ): ?array {
        $splitter->setChunkFileNameTemplate($this->fileNameProvider->getChunkFileNameTemplate($operationId));
        if ($splitter instanceof PartialFileSplitterInterface) {
            $splitter->setTimeout($this->splitFileTimeout);
            $splitter->setState($splitterState);
        }

        $initialChunkSize = $splitter->getChunkSize();
        $initialChunkSizePerSection = $splitter->getChunkSizePerSection();
        $splitter->setChunkSize($body['chunkSize']);
        $chunkSizePerSection = [];
        foreach ($initialChunkSizePerSection as $sectionName => $chunkSize) {
            $chunkSizePerSection[$sectionName] = $body['includedDataChunkSize'];
        }
        $splitter->setChunkSizePerSection($chunkSizePerSection);
        try {
            return $splitter->splitFile($dataFileName, $this->sourceDataFileManager, $this->fileManager);
        } catch (\Exception $e) {
            $this->handleSplitterException($e, $body);

            return null;
        } finally {
            $splitter->setChunkSize($initialChunkSize);
            $splitter->setChunkSizePerSection($initialChunkSizePerSection);
        }
    }

    private function handleSplitterException(\Exception $exception, array $body): void
    {
        $operationId = $body['operationId'];
        $dataFileName = $body['fileName'];

        $this->safeDeleteDataFile($dataFileName);

        $errorMessage = $exception->getMessage();
        $errorException = $exception;
        if ($exception instanceof FileSplitterException) {
            // remove all target files that were already created before the failure
            foreach ($exception->getTargetFileNames() as $fileName) {
                $this->processingHelper->safeDeleteFile($fileName);
            }
            $this->processingHelper->safeDeleteChunkFiles(
                $operationId,
                $this->fileNameProvider->getChunkFileNameTemplate($operationId)
            );

            $errorMessage = 'Failed to parse the data file.';
            if (null !== $exception->getPrevious()) {
                $errorMessage .= ' ' . $exception->getPrevious()->getMessage();
            }
            if ($exception instanceof ParsingErrorFileSplitterException) {
                // remove invalid UTF-8 characters from the error message
                $errorMessage = mb_convert_encoding($errorMessage, 'UTF-8', 'UTF-8');
            }
            $errorException = null;
        }

        $errorContext = [];
        if (null !== $errorException) {
            $errorContext['exception'] = $errorException;
        }
        $this->logger->error(
            sprintf('The splitting of the file "%s" failed. Reason: %s', $dataFileName, $errorMessage),
            $errorContext
        );

        $this->operationManager->markAsFailed($operationId, $dataFileName, $errorMessage);
    }

    public function safeDeleteDataFile(string $fileName): void
    {
        try {
            $this->sourceDataFileManager->deleteFile($fileName);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('The deletion of the file "%s" failed.', $fileName),
                ['exception' => $e]
            );
        }
    }
}
