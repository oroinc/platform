<?php
declare(strict_types=1);

namespace Oro\Component\MessageQueue\Client\Meta;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists available message queue destinations.
 */
class DestinationsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:message-queue:destinations';

    private DestinationMetaRegistry $destinationMetaRegistry;

    public function __construct(DestinationMetaRegistry $destinationMetaRegistry)
    {
        $this->destinationMetaRegistry = $destinationMetaRegistry;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Lists available message queue destinations.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command lists available message queue destinations.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['Client Name', 'Transport Name', 'Subscribers']);

        $count = 0;
        $firstRow = true;
        foreach ($this->destinationMetaRegistry->getDestinationsMeta() as $destination) {
            if (!$firstRow) {
                $table->addRow(new TableSeparator());
            }

            $table->addRow([
                $destination->getQueueName(),
                $destination->getTransportQueueName(),
                implode(PHP_EOL, $destination->getMessageProcessors())
            ]);

            $count++;
            $firstRow = false;
        }

        $output->writeln(sprintf('Found %s destinations', $count));
        $output->writeln('');
        $table->render();

        return 0;
    }
}
