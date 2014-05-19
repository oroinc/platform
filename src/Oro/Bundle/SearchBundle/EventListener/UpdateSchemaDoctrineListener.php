<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand;

use Symfony\Component\Console\Event\ConsoleTerminateEvent;

use Oro\Bundle\SearchBundle\Engine\AbstractEngine;

class UpdateSchemaDoctrineListener
{
    /**
     * @var AbstractEngine
     */
    protected $searchEngine;

    /**
     * @param AbstractEngine $searchEngine
     */
    public function __construct(AbstractEngine $searchEngine)
    {
        $this->searchEngine = $searchEngine;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        if ($event->getCommand() instanceof UpdateSchemaDoctrineCommand) {
            $output = $event->getOutput();
            $input  = $event->getInput();

            if ($input->getOption('force')) {
                $count = $this->searchEngine->reindex();

                $output->writeln(
                    sprintf('Schema update and create index completed. %d index entities were added', $count)
                );
            }
        }
    }
}
