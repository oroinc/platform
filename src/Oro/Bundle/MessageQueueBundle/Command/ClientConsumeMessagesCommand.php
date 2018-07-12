<?php

namespace Oro\Bundle\MessageQueueBundle\Command;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ChainExtension;
use Oro\Component\MessageQueue\Client\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Сonsume messages from selected queue (or list of all available queues if queue is not defined)
 */
class ClientConsumeMessagesCommand extends ConsumeMessagesCommand
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
     * {@inheritdoc}
     */
    protected function getConsumerExtension(array $extensions)
    {
        return new ChainExtension($extensions, $this->getConsumerState());
    }

    /**
     * {@inheritdoc}
     */
    protected function getLoggerExtension(InputInterface $input, OutputInterface $output)
    {
        return new LoggerExtension($this->container->get('logger'));
    }

    /**
     * @return ConsumerState
     */
    protected function getConsumerState()
    {
        return $this->container->get('oro_message_queue.log.consumer_state');
    }
}
