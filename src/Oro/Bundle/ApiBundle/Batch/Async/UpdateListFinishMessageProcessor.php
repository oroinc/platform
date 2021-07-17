<?php

namespace Oro\Bundle\ApiBundle\Batch\Async;

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
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Finishes the processing of API batch update request.
 */
class UpdateListFinishMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var UpdateListProcessingHelper */
    private $processingHelper;

    /** @var AsyncOperationManager */
    private $operationManager;

    /** @var FileManager */
    private $fileManager;

    /** @var IncludeMapManager */
    private $includeMapManager;

    /** @var FileNameProvider */
    private $fileNameProvider;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        UpdateListProcessingHelper $processingHelper,
        AsyncOperationManager $operationManager,
        FileManager $fileManager,
        IncludeMapManager $includeMapManager,
        FileNameProvider $fileNameProvider,
        LoggerInterface $logger
    ) {
        $this->processingHelper = $processingHelper;
        $this->operationManager = $operationManager;
        $this->fileManager = $fileManager;
        $this->includeMapManager = $includeMapManager;
        $this->fileNameProvider = $fileNameProvider;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::UPDATE_LIST_FINISH];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $startTimestamp = microtime(true);
        $body = JSON::decode($message->getBody());
        if (!isset(
            $body['operationId'],
            $body['entityClass'],
            $body['requestType'],
            $body['version'],
            $body['fileName']
        )) {
            $this->logger->critical('Got invalid message.');

            return self::REJECT;
        }

        $operationId = $body['operationId'];
        $this->handleNotProcessedIncludedItems($operationId, $body['fileName']);
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
