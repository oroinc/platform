<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand;

class DemoDataMigrationListener
{
    /** @var IndexerInterface */
    protected $searchIndexer;

    /** @var IndexListener */
    protected $searchListener;

    /** @var bool|null */
    protected $isExceptionOccurred;

    /**
     * @param IndexerInterface $searchIndexer
     * @param IndexListener $searchListener
     */
    public function __construct(IndexerInterface $searchIndexer, IndexListener $searchListener)
    {
        $this->searchIndexer = $searchIndexer;
        $this->searchListener = $searchListener;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if ($this->isProcessingRequired($event)) {
            $this->searchListener->setEnabled(false);
        }
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        if (!$this->isExceptionOccurred && $this->isProcessingRequired($event)) {
            if ($event->getExitCode() === 0) {
                $this->searchIndexer->reindex();
            }
            $this->searchListener->setEnabled(true);
        }
        $this->isExceptionOccurred = null;
    }

    /**
     * @param ConsoleExceptionEvent $event
     */
    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        $this->isExceptionOccurred = true;
    }

    /**
     * @param ConsoleEvent $event
     * @return bool
     */
    protected function isProcessingRequired(ConsoleEvent $event)
    {
        return $event->getCommand() instanceof LoadDataFixturesCommand
            && $event->getInput()->hasOption('fixtures-type')
            && $event->getInput()->getOption('fixtures-type') == LoadDataFixturesCommand::DEMO_FIXTURES_TYPE;
    }
}
