<?php
declare(strict_types=1);

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates required message queues.
 */
class CreateQueuesCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:message-queue:create-queues';

    private DriverInterface $clientDriver;
    private DestinationMetaRegistry $destinationMetaRegistry;

    public function __construct(DriverInterface $clientDriver, DestinationMetaRegistry $destinationMetaRegistry)
    {
        $this->clientDriver = $clientDriver;
        $this->destinationMetaRegistry = $destinationMetaRegistry;
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Creates required message queues.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command creates required message queues.

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
        foreach ($this->destinationMetaRegistry->getDestinationsMeta() as $meta) {
            $output->writeln(sprintf('Creating queue: <comment>%s</comment>', $meta->getTransportQueueName()));

            $this->clientDriver->createQueue($meta->getTransportQueueName());
        }

        return 0;
    }
}
