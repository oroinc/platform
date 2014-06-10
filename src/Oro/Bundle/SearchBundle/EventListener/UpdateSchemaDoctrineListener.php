<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand;

use Symfony\Component\Console\Event\ConsoleTerminateEvent;

use Oro\Bundle\SearchBundle\Engine\FulltextIndexManager;

class UpdateSchemaDoctrineListener
{
    /**
     * @var FulltextIndexManager
     */
    protected $fulltextIndexManager;

    /**
     * @param FulltextIndexManager $fulltextIndexManager
     */
    public function __construct(FulltextIndexManager $fulltextIndexManager)
    {
        $this->fulltextIndexManager = $fulltextIndexManager;
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
                $result = $this->fulltextIndexManager->createIndexes();

                $output->writeln('Schema update and create index completed.');
                if ($result) {
                    $output->writeln('Indexes were created.');
                }
            }
        }
    }
}
