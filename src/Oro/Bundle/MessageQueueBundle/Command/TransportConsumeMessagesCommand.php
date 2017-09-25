<?php

namespace Oro\Bundle\MessageQueueBundle\Command;

use Oro\Component\MessageQueue\Consumption\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;

use Oro\Bundle\MessageQueueBundle\Log\ConsumerState;

class TransportConsumeMessagesCommand extends ConsumeMessagesCommand
{
    /**
     * {@inheritdoc}
     */
    protected function consume(QueueConsumer $consumer, ExtensionInterface $extension)
    {
        $consumerState = $this->getConsumerState();
        $consumerState->startConsumption();
        try {
            parent::consume($consumer, $extension);
        } finally {
            $consumerState->stopConsumption();
        }
    }

    /**
     * @return ConsumerState
     */
    protected function getConsumerState()
    {
        return $this->container->get('oro_message_queue.log.consumer_state');
    }
}
