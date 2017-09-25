<?php

namespace Oro\Component\MessageQueue\Client;

use Psr\Log\LoggerInterface;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\LimitsExtensionsCommandTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;

class ConsumeMessagesCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use LimitsExtensionsCommandTrait;

    /**
     * @var QueueConsumer
     * @deprecated It's not being used anymore. Please use $this->getConsumer(). Left for BC
     */
    protected $consumer;

    /**
     * @var DelegateMessageProcessor
     * @deprecated It's not being used anymore. Please use $this->getProcessor(). Left for BC
     */
    protected $processor;

    /**
     * @param QueueConsumer $consumer
     * @param DelegateMessageProcessor $processor
     * @param DestinationMetaRegistry $destinationMetaRegistry
     * @param LoggerInterface $logger
     *
     * @deprecated Dependencies are now taken from container. Left for BC
     */
    public function __construct(
        QueueConsumer $consumer,
        DelegateMessageProcessor $processor,
        DestinationMetaRegistry $destinationMetaRegistry,
        LoggerInterface $logger
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->configureLimitsExtensions();

        $this
            ->setName('oro:message-queue:consume')
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
        if ($clientDestinationName = $input->getArgument('clientDestinationName')) {
            $this->getConsumer()->bind(
                $this->getDestinationMetaRegistry()->getDestinationMeta($clientDestinationName)->getTransportName(),
                $this->getProcessor()
            );
        } else {
            foreach ($this->getDestinationMetaRegistry()->getDestinationsMeta() as $destinationMeta) {
                $this->getConsumer()->bind(
                    $destinationMeta->getTransportName(),
                    $this->getProcessor()
                );
            }
        }

        $extensions = $this->getLimitsExtensions($input, $output);
        array_unshift($extensions, new LoggerExtension($this->getLogger()));

        $this->consume($this->getConsumer(), $this->getConsumerExtension($extensions));
    }

    /**
     * @param QueueConsumer      $consumer
     * @param ExtensionInterface $extension
     *
     * @throws \Exception
     */
    protected function consume(QueueConsumer $consumer, ExtensionInterface $extension)
    {
        try {
            $consumer->consume($extension);
        } catch (\Exception $e) {
            $this->getLogger()->error(
                sprintf('Consume messages command exception. "%s"', $e->getMessage()),
                ['exception' => $e]
            );

            throw $e;
        } finally {
            $this->getConsumer()->getConnection()->close();
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
     * @return QueueConsumer
     */
    private function getConsumer()
    {
        return $this->container->get('oro_message_queue.client.queue_consumer');
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger()
    {
        return $this->container->get('logger');
    }

    /**
     * @return DestinationMetaRegistry
     */
    private function getDestinationMetaRegistry()
    {
        return $this->container->get('oro_message_queue.client.meta.destination_meta_registry');
    }

    /**
     * @return MessageProcessorInterface
     */
    private function getProcessor()
    {
        return $this->container->get('oro_message_queue.client.delegate_message_processor');
    }
}
