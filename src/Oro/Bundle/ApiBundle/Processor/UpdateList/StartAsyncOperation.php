<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListTopic;
use Oro\Bundle\ApiBundle\Batch\ChunkSizeProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends a message to the message queue to start the asynchronous operation.
 */
class StartAsyncOperation implements ProcessorInterface
{
    public const OPERATION_NAME = 'start_async_operation';

    private MessageProducerInterface $producer;
    private ChunkSizeProvider $chunkSizeProvider;

    public function __construct(MessageProducerInterface $producer, ChunkSizeProvider $chunkSizeProvider)
    {
        $this->producer = $producer;
        $this->chunkSizeProvider = $chunkSizeProvider;
    }

    /**
     * {@inheritdoc}
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
        $this->producer->send(
            UpdateListTopic::getName(),
            [
                'operationId'           => $operationId,
                'entityClass'           => $entityClass,
                'requestType'           => $context->getRequestType()->toArray(),
                'version'               => $context->getVersion(),
                'fileName'              => $targetFileName,
                'chunkSize'             => $this->chunkSizeProvider->getChunkSize($entityClass),
                'includedDataChunkSize' => $this->chunkSizeProvider->getIncludedDataChunkSize($entityClass)
            ]
        );

        $context->setProcessed(self::OPERATION_NAME);
    }
}
