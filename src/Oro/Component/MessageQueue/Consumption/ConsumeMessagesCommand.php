<?php

namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A worker that consumes message from a broker.
 */
class ConsumeMessagesCommand extends Command
{
    use LimitsExtensionsCommandTrait;

    /** @var string */
    protected static $defaultName = 'oro:message-queue:transport:consume';

    /** @var QueueConsumer */
    protected $queueConsumer;

    /** @var ContainerInterface */
    protected $processorLocator;

    /**
     * @param QueueConsumer $queueConsumer
     * @param ContainerInterface $processorLocator
     */
    public function __construct(QueueConsumer $queueConsumer, ContainerInterface $processorLocator)
    {
        parent::__construct();

        $this->queueConsumer = $queueConsumer;
        $this->processorLocator = $processorLocator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->configureLimitsExtensions();

        $this
            ->setDescription('A worker that consumes message from a broker. '.
                'To use this broker you have to explicitly set a queue to consume from '.
                'and a message processor service')
            ->addArgument('queue', InputArgument::REQUIRED, 'Queues to consume from')
            ->addArgument('processor-service', InputArgument::REQUIRED, 'A message processor service');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queueName = $input->getArgument('queue');
        $messageProcessor = $this->getMessageProcessor($input->getArgument('processor-service'));

        $extensions = $this->getLimitsExtensions($input, $output);
        array_unshift($extensions, $this->getLoggerExtension($input, $output));

        $this->queueConsumer->bind($queueName, $messageProcessor);
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

    /**
     * @param string $processorServiceId
     *
     * @return MessageProcessorInterface
     */
    private function getMessageProcessor($processorServiceId)
    {
        $processor = $this->processorLocator->get($processorServiceId);
        if (!$processor instanceof MessageProcessorInterface) {
            throw new \LogicException(sprintf(
                'Invalid message processor service given. It must be an instance of %s but %s',
                MessageProcessorInterface::class,
                get_class($processor)
            ));
        }

        return $processor;
    }
}
