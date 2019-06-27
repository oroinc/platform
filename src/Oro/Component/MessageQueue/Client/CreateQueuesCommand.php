<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates all required queues for consumer
 */
class CreateQueuesCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:message-queue:create-queues';

    /** @var DriverInterface */
    private $clientDriver;

    /** @var DestinationMetaRegistry */
    private $destinationMetaRegistry;

    /**
     * @param DriverInterface $clientDriver
     * @param DestinationMetaRegistry $destinationMetaRegistry
     */
    public function __construct(DriverInterface $clientDriver, DestinationMetaRegistry $destinationMetaRegistry)
    {
        $this->clientDriver = $clientDriver;
        $this->destinationMetaRegistry = $destinationMetaRegistry;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Creates all required queues');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->destinationMetaRegistry->getDestinationsMeta() as $meta) {
            $output->writeln(sprintf('Creating queue: <comment>%s</comment>', $meta->getTransportName()));

            $this->clientDriver->createQueue($meta->getTransportName());
        }
    }
}
