<?php

namespace Oro\Component\MessageQueue\Client\Meta;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class DestinationsCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:message-queue:destinations')
            ->setDescription('A command shows all available destinations and some information about them.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['Client Name', 'Transport Name', 'Subscribers']);

        $count = 0;
        $firstRow = true;
        /** @var DestinationMetaRegistry $destinationMetaRegistry */
        $destinationMetaRegistry = $this->container->get('oro_message_queue.client.meta.destination_meta_registry');
        foreach ($destinationMetaRegistry->getDestinationsMeta() as $destination) {
            if (!$firstRow) {
                $table->addRow(new TableSeparator());
            }

            $table->addRow([
                $destination->getClientName(),
                $destination->getTransportName(),
                implode(PHP_EOL, $destination->getSubscribers())
            ]);

            $count++;
            $firstRow = false;
        }

        $output->writeln(sprintf('Found %s destinations', $count));
        $output->writeln('');
        $table->render();
    }
}
