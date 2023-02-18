<?php

namespace Oro\Bundle\ApiBundle\Batch\Async;

use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListFinishTopic;
use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Batch\IncludeMapManager;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Finishes the processing of API batch update request.
 */
class UpdateListFinishMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private UpdateListProcessingHelper $processingHelper;
    private AsyncOperationManager $operationManager;
    private FileManager $fileManager;
    private IncludeMapManager $includeMapManager;
    private FileNameProvider $fileNameProvider;

    public function __construct(
        UpdateListProcessingHelper $processingHelper,
        AsyncOperationManager $operationManager,
        FileManager $fileManager,
        IncludeMapManager $includeMapManager,
        FileNameProvider $fileNameProvider
    ) {
        $this->processingHelper = $processingHelper;
        $this->operationManager = $operationManager;
        $this->fileManager = $fileManager;
        $this->includeMapManager = $includeMapManager;
        $this->fileNameProvider = $fileNameProvider;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [UpdateListFinishTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $startTimestamp = microtime(true);
        $messageBody = $message->getBody();

        $operationId = $messageBody['operationId'];
        $this->handleNotProcessedIncludedItems($operationId, $messageBody['fileName']);
        $this->processingHelper->safeDeleteFile(
            $this->fileNameProvider->getInfoFileName($operationId)
        );
        $this->processingHelper->safeDeleteFile(
            $this->fileNameProvider->getIncludeIndexFileName($operationId)
        );
        $this->processingHelper->safeDeleteFile(
            $this->fileNameProvider->getProcessedIncludeIndexFileName($operationId)
        );
        $this->processingHelper->safeDeleteFile(
            $this->fileNameProvider->getLinkedIncludeIndexFileName($operationId)
        );

        $this->operationManager->incrementAggregateTime(
            $operationId,
            $this->processingHelper->calculateAggregateTime($startTimestamp)
        );

        return self::ACK;
    }

    private function handleNotProcessedIncludedItems(int $operationId, string $dataFileName): void
    {
        $notLinkedIncludedItemIndexes = $this->includeMapManager->getNotLinkedIncludedItemIndexes(
            $this->fileManager,
            $operationId
        );
        if (!$notLinkedIncludedItemIndexes) {
            return;
        }

        $errors = [];
        foreach ($notLinkedIncludedItemIndexes as $sectionName => $itemIndexes) {
            foreach ($itemIndexes as $itemIndex) {
                $error = BatchError::createValidationError(
                    Constraint::REQUEST_DATA,
                    'The entity should have a relationship with at least one primary entity'
                    . ' and this should be explicitly specified in the request'
                );
                $error->setSource(ErrorSource::createByPointer(sprintf('/%s/%s', $sectionName, $itemIndex)));
                $errors[] = $error;
            }
        }

        $this->operationManager->addErrors($operationId, $dataFileName, $errors);
    }
}
