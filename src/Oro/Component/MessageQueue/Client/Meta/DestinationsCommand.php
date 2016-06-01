<?php
namespace Oro\Component\MessageQueue\Client\Meta;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DestinationsCommand extends Command
{
    /**
     * @var DestinationMetaRegistry
     */
    private $destinationRegistry;

    /**
     * @param DestinationMetaRegistry $destinationRegistry
     */
    public function __construct(DestinationMetaRegistry $destinationRegistry)
    {
        parent::__construct('oro:message-queue:destinations');

        $this->destinationRegistry = $destinationRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('A command shows all available destinations and some information about them.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['Client Name', 'Transport Name', 'Subscribers']);

        $count = 0;
        foreach ($this->destinationRegistry->getDestinationsMeta() as $destination) {
            $table->addRow([
                $destination->getClientName() ?: 'NULL',
                $destination->getTransportName(),
                implode(PHP_EOL, $destination->getSubscribers())
            ]);

            $count++;
        }

        $output->writeln(sprintf('Found %s destinations', $count));
        $output->writeln('');
        $table->render();
    }
}
