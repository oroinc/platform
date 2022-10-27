<?php

namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Oro\Component\MessageQueue\Log\MessageProcessorClassProvider;

/**
 * Updates the consumer state with the current message processor and message.
 */
class ConsumptionExtension extends AbstractExtension
{
    private ConsumerState $consumerState;

    private MessageProcessorClassProvider $messageProcessorClassProvider;

    public function __construct(
        ConsumerState $consumerState,
        MessageProcessorClassProvider $messageProcessorClassProvider
    ) {
        $this->consumerState = $consumerState;
        $this->messageProcessorClassProvider = $messageProcessorClassProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context): void
    {
        $this->consumerState->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context): void
    {
        $messageProcessorName = $context->getMessageProcessorName();
        $messageProcessorClass = $this->messageProcessorClassProvider
            ->getMessageProcessorClassByName($messageProcessorName);

        $this->consumerState->setMessageProcessorName($messageProcessorName);
        $this->consumerState->setMessageProcessorClass($messageProcessorClass);
        $this->consumerState->setMessage($context->getMessage());
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context): void
    {
        $this->consumerState->setMessageProcessorName();
        $this->consumerState->setMessageProcessorClass();
        $this->consumerState->setMessage();
    }
}
