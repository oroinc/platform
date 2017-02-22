<?php

namespace Oro\Component\MessageQueue\Client;

use Psr\Log\LoggerInterface;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

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
        $consumer = $this->getConsumer();
        $clientDestinationName = $input->getArgument('clientDestinationName');
        if ($clientDestinationName) {
            $consumer->bind(
                $this->getDestinationMetaRegistry()->getDestinationMeta($clientDestinationName)->getTransportName(),
                $this->getProcessor()
            );
        } else {
            foreach ($this->getDestinationMetaRegistry()->getDestinationsMeta() as $destinationMeta) {
                $consumer->bind(
                    $destinationMeta->getTransportName(),
                    $this->getProcessor()
                );
            }
        }

        $extensions = $this->getLimitsExtensions($input, $output);
        array_unshift($extensions, new LoggerExtension(new ConsoleLogger($output)));

        $runtimeExtensions = new ChainExtension($extensions);

        try {
            $consumer->consume($runtimeExtensions);
        } catch (\Exception $e) {
            $this->getLogger()->error(
                sprintf('Consume messages command exception. "%s"', $e->getMessage()),
                ['exception' => $e]
            );

            throw $e;
        } finally {
            $consumer->getConnection()->close();
        }
    }

    /**
     * @return QueueConsumer
     */
    private function getConsumer()
    {
        return $this->container->get('oro_message_queue.client.queue_consumer');
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

    /**
     * @return LoggerInterface
     */
    private function getLogger()
    {
        return $this->container->get('logger');
    }
}
