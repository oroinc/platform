<?php

namespace Oro\Bundle\ApiBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Async\Topic\DeleteAsyncOperationTopic;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Deletes an asynchronous operation.
 */
class DeleteAsyncOperationMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private ManagerRegistry $doctrine;
    private EntityDeleteHandlerRegistry $deleteHandlerRegistry;
    private LoggerInterface $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        EntityDeleteHandlerRegistry $deleteHandlerRegistry,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->deleteHandlerRegistry = $deleteHandlerRegistry;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [DeleteAsyncOperationTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();

        $operationId = $messageBody['operationId'];
        $operation = $this->doctrine->getManagerForClass(AsyncOperation::class)
            ->find(AsyncOperation::class, $operationId);
        if (null === $operation) {
            $this->logger->warning(sprintf('The asynchronous operation with ID %d was not found.', $operationId));

            return self::REJECT;
        }

        $deleteHandler = $this->deleteHandlerRegistry->getHandler(AsyncOperation::class);
        try {
            $deleteHandler->delete($operation);
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('The asynchronous operation with ID %d was not deleted.', $operationId),
                ['exception' => $e]
            );

            return self::REJECT;
        }

        return self::ACK;
    }
}
