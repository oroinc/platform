<?php
declare(strict_types=1);

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
 * Processes messages from the message-queue.
 */
class ClientConsumeMessagesCommand extends ConsumeMessagesCommand
{
    /** @var string */
    protected static $defaultName = 'oro:message-queue:consume';

    private ConsumerState $consumerState;
    private LoggerInterface $logger;

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

    protected function consume(QueueConsumer $consumer, ExtensionInterface $extension): void
    {
        $this->consumerState->startConsumption();
        try {
            parent::consume($consumer, $extension);
        } finally {
            $this->consumerState->stopConsumption();
        }
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function getConsumerExtension(array $extensions): ExtensionInterface
    {
        return new ChainExtension($extensions, $this->consumerState);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function getLoggerExtension(InputInterface $input, OutputInterface $output): ExtensionInterface
    {
        return new LoggerExtension($this->logger);
    }
}
