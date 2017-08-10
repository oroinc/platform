<?php

namespace Oro\Component\MessageQueue\Consumption;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;

class ConsumeMessagesCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use LimitsExtensionsCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->configureLimitsExtensions();

        $this
            ->setName('oro:message-queue:transport:consume')
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
        $consumer = $this->getConsumer();

        $extensions = $this->getLimitsExtensions($input, $output);
        array_unshift($extensions, $this->getLoggerExtension($input, $output));

        $consumer->bind($queueName, $messageProcessor);
        $this->consume($consumer, $this->getConsumerExtension($extensions));
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
        $processor = $this->container->get($processorServiceId);
        if (!$processor instanceof MessageProcessorInterface) {
            throw new \LogicException(sprintf(
                'Invalid message processor service given. It must be an instance of %s but %s',
                MessageProcessorInterface::class,
                get_class($processor)
            ));
        }

        return $processor;
    }

    /**
     * @return QueueConsumer
     */
    private function getConsumer()
    {
        return $this->container->get('oro_message_queue.consumption.queue_consumer');
    }
}
