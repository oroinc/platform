<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\LimitsExtensionsCommandTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A client's worker that processes messages.
 */
class ConsumeMessagesCommand extends Command
{
    use LimitsExtensionsCommandTrait;

    /** @var string */
    protected static $defaultName = 'oro:message-queue:consume';

    /** @var QueueConsumer */
    protected $queueConsumer;

    /** @var DestinationMetaRegistry */
    protected $destinationMetaRegistry;

    /** @var MessageProcessorInterface */
    protected $messageProcessor;

    /**
     * @param QueueConsumer $queueConsumer
     * @param DestinationMetaRegistry $destinationMetaRegistry
     * @param MessageProcessorInterface $messageProcessor
     */
    public function __construct(
        QueueConsumer $queueConsumer,
        DestinationMetaRegistry $destinationMetaRegistry,
        MessageProcessorInterface $messageProcessor
    ) {
        parent::__construct();

        $this->queueConsumer = $queueConsumer;
        $this->destinationMetaRegistry = $destinationMetaRegistry;
        $this->messageProcessor = $messageProcessor;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->configureLimitsExtensions();

        $this
            ->setDescription('A client\'s worker that processes messages. '.
                'By default it connects to default queue. '.
                'It select an appropriate message processor based on a message headers')
            ->addArgument('clientDestinationName', InputArgument::OPTIONAL, 'Queues to consume messages from');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clientDestinationName = $input->getArgument('clientDestinationName');
        if ($clientDestinationName) {
            $this->queueConsumer->bind(
                $this->destinationMetaRegistry->getDestinationMeta($clientDestinationName)->getTransportName(),
                $this->messageProcessor
            );
        } else {
            foreach ($this->destinationMetaRegistry->getDestinationsMeta() as $destinationMeta) {
                $this->queueConsumer->bind(
                    $destinationMeta->getTransportName(),
                    $this->messageProcessor
                );
            }
        }

        $extensions = $this->getLimitsExtensions($input, $output);
        array_unshift($extensions, $this->getLoggerExtension($input, $output));

        $this->consume($this->queueConsumer, $this->getConsumerExtension($extensions));
    }

    /**
     * @param QueueConsumer      $consumer
     * @param ExtensionInterface $extension
     */
    protected function consume(QueueConsumer $consumer, ExtensionInterface $extension)
    {
        try {
            $consumer->consume($extension);
        } finally {
            $consumer->getConnection()->close();
        }
    }

    /**
     * @param array $extensions
     *
     * @return ExtensionInterface
     */
    protected function getConsumerExtension(array $extensions)
    {
        return new ChainExtension($extensions);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return ExtensionInterface
     */
    protected function getLoggerExtension(InputInterface $input, OutputInterface $output)
    {
        return new LoggerExtension(new ConsoleLogger($output));
    }
}
