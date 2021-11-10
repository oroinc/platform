<?php

namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Log\ConsumerState;

/**
 * Updates the consumer state with the current message processor and message.
 */
class ConsumptionExtension extends AbstractExtension
{
    private ConsumerState $consumerState;

    public function __construct(ConsumerState $consumerState)
    {
        $this->consumerState = $consumerState;
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
        $this->consumerState->setMessageProcessorName($context->getMessageProcessorName());
        $this->consumerState->setMessage($context->getMessage());
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context): void
    {
        $this->consumerState->setMessageProcessorName();
        $this->consumerState->setMessage();
    }
}
