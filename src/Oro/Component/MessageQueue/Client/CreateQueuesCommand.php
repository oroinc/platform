<?php
namespace Oro\Component\MessageQueue\Client;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;

class CreateQueuesCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:message-queue:create-queues')
            ->setDescription('Creates all required queues');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var DriverInterface $driver */
        $driver = $this->container->get('oro_message_queue.client.driver');
        /** @var DestinationMetaRegistry $destinationMetaRegistry */
        $destinationMetaRegistry = $this->container->get('oro_message_queue.client.meta.destination_meta_registry');
        foreach ($destinationMetaRegistry->getDestinationsMeta() as $meta) {
            $output->writeln(sprintf('Creating queue: <comment>%s</comment>', $meta->getTransportName()));

            $driver->createQueue($meta->getTransportName());
        }
    }
}
