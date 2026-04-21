<?php

namespace Oro\Bundle\LoggerBundle\Async;

use Oro\Bundle\LoggerBundle\Trace\TraceManagerInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerMiddlewareInterface;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Ensures that every MQ message has a traceId property for log traceability
 */
class MessageTraceProducerMiddleware extends AbstractExtension implements MessageProducerMiddlewareInterface
{
    private const MESSAGE_PROPERTY_TRACE_ID = 'traceId';

    private ?string $currentTraceId = null;

    public function __construct(
        private readonly TraceManagerInterface $traceManager,
    ) {
    }

    /**
     * Persist traceId from parent message when consuming from queue
     * Stores it locally for propagation to child messages
     */
    #[\Override]
    public function onPostReceived(Context $context): void
    {
        $this->currentTraceId = $context->getMessage()
            ->getProperty(self::MESSAGE_PROPERTY_TRACE_ID) ?: null;
    }

    /**
     * Add traceId to message for log traceability.
     * Uses priority chain: message property → current message context → trace manager → generate new
     */
    #[\Override]
    public function handle(Message $message): void
    {
        if (null !== $message->getProperty(self::MESSAGE_PROPERTY_TRACE_ID)) {
            return;
        }

        $traceId = $this->currentTraceId ?? $this->traceManager->get();

        if (null === $traceId) {
            $traceId = $this->traceManager->generate();
            $this->traceManager->set($traceId);
        }

        $message->setProperty(self::MESSAGE_PROPERTY_TRACE_ID, $traceId);
    }
}
