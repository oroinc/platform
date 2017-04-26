<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleTerminateEvent;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand;

class ReindexDemoDataListener
{
    /**
     * @var IndexerInterface
     */
    protected $searchIndexer;

    /**
     * @param IndexerInterface $indexer
     */
    public function __construct(IndexerInterface $indexer)
    {
        $this->searchIndexer = $indexer;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function afterExecute(ConsoleTerminateEvent $event)
    {
        $commandName = $event->getCommand()->getName();

        if ($commandName !== LoadDataFixturesCommand::COMMAND_NAME || $event->getExitCode() !== 0) {
            return;
        }

        if ($event->getInput()->getOption('fixtures-type') === LoadDataFixturesCommand::DEMO_FIXTURES_TYPE) {
            $this->searchIndexer->reindex();
        }
    }
}
