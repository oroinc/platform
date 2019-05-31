<?php

namespace Oro\Bundle\MessageQueueBundle\Command;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ChainExtension;
use Oro\Component\MessageQueue\Consumption\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Consume messages from selected queue (or list of all available queues if queue is not defined) with own processor
 */
class TransportConsumeMessagesCommand extends ConsumeMessagesCommand
{
    /** @var string */
    protected static $defaultName = 'oro:message-queue:transport:consume';

    /** @var ConsumerState */
    private $consumerState;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param QueueConsumer $queueConsumer
     * @param ContainerInterface $processorLocator
     * @param ConsumerState $consumerState
     * @param LoggerInterface $logger
     */
    public function __construct(
        QueueConsumer $queueConsumer,
        ContainerInterface $processorLocator,
        ConsumerState $consumerState,
        LoggerInterface $logger
    ) {
        parent::__construct($queueConsumer, $processorLocator);

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
