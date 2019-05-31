<?php

namespace Oro\Bundle\MessageQueueBundle\Command;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ChainExtension;
use Oro\Component\MessageQueue\Client\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Сonsume messages from selected queue (or list of all available queues if queue is not defined)
 */
class ClientConsumeMessagesCommand extends ConsumeMessagesCommand
{
    /** @var string */
    protected static $defaultName = 'oro:message-queue:consume';

    /** @var ConsumerState */
    private $consumerState;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param QueueConsumer $queueConsumer
     * @param DestinationMetaRegistry $destinationMetaRegistry
     * @param MessageProcessorInterface $messageProcessor
     * @param ConsumerState $consumerState
     * @param LoggerInterface $logger
     */
    public function __construct(
        QueueConsumer $queueConsumer,
        DestinationMetaRegistry $destinationMetaRegistry,
        MessageProcessorInterface $messageProcessor,
        ConsumerState $consumerState,
        LoggerInterface $logger
    ) {
        parent::__construct($queueConsumer, $destinationMetaRegistry, $messageProcessor);

        $this->consumerState = $consumerState;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function consume(QueueConsumer $consumer, ExtensionInterface $extension)
    {
        $this->consumerState->startConsumption();
        try {
            parent::consume($consumer, $extension);
        } finally {
            $this->consumerState->stopConsumption();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getConsumerExtension(array $extensions)
    {
        return new ChainExtension($extensions, $this->consumerState);
    }

    /**
     * {@inheritdoc}
     */
    protected function getLoggerExtension(InputInterface $input, OutputInterface $output)
    {
        return new LoggerExtension($this->logger);
    }
}
