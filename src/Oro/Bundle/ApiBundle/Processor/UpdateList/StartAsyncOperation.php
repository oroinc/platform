<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListTopic;
use Oro\Bundle\ApiBundle\Batch\ChunkSizeProvider;
use Oro\Bundle\ApiBundle\Batch\SyncProcessingLimitProvider;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends a message to the message queue to start the asynchronous operation.
 */
class StartAsyncOperation implements ProcessorInterface
{
    public const OPERATION_NAME = 'start_async_operation';

    private MessageProducerInterface $producer;
    private ChunkSizeProvider $chunkSizeProvider;
    private SyncProcessingLimitProvider $syncProcessingLimitProvider;

    public function __construct(
        MessageProducerInterface $producer,
        ChunkSizeProvider $chunkSizeProvider,
        SyncProcessingLimitProvider $syncProcessingLimitProvider
    ) {
        $this->producer = $producer;
        $this->chunkSizeProvider = $chunkSizeProvider;
        $this->syncProcessingLimitProvider = $syncProcessingLimitProvider;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the asynchronous operation is already started
            return;
        }

        $operationId = $context->getOperationId();
        if (null === $operationId) {
            return;
        }

        $targetFileName = $context->getTargetFileName();
        if (!$targetFileName) {
            throw new \RuntimeException('The target file name was not set to the context.');
        }

        $entityClass = $context->getClassName();
        $synchronousMode = $context->isSynchronousMode();
        $chunkSize = $synchronousMode
            ? $this->syncProcessingLimitProvider->getLimit($entityClass)
            : $this->chunkSizeProvider->getChunkSize($entityClass);
        $includedDataChunkSize = $synchronousMode
            ? $this->syncProcessingLimitProvider->getIncludedDataLimit($entityClass)
            : $this->chunkSizeProvider->getIncludedDataChunkSize($entityClass);
        $this->producer->send(
            UpdateListTopic::getName(),
            new Message([
                'operationId'           => $operationId,
                'entityClass'           => $entityClass,
                'requestType'           => $context->getRequestType()->toArray(),
                'version'               => $context->getVersion(),
                'synchronousMode'       => $synchronousMode,
                'fileName'              => $targetFileName,
                'chunkSize'             => $chunkSize,
                'includedDataChunkSize' => $includedDataChunkSize
            ], $synchronousMode ? MessagePriority::HIGH : MessagePriority::NORMAL)
        );
        if ($synchronousMode
            && $this->producer instanceof BufferedMessageProducer
            && $this->producer->isBufferingEnabled()
        ) {
            $this->producer->flushBuffer();
        }

        $context->setProcessed(self::OPERATION_NAME);
    }
}
