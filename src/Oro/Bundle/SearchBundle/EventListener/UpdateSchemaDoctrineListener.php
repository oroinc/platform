<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand;

use Oro\Bundle\SearchBundle\DependencyInjection\Configuration;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

use Oro\Bundle\SearchBundle\Engine\FulltextIndexManager;
use Symfony\Component\DependencyInjection\Container;

class UpdateSchemaDoctrineListener
{
    /**
     * @var FulltextIndexManager
     */
    protected $fulltextIndexManager;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @param FulltextIndexManager $fulltextIndexManager
     * @param Container            $container
     */
    public function __construct(FulltextIndexManager $fulltextIndexManager, Container $container)
    {
        $this->container            = $container;
        $this->fulltextIndexManager = $fulltextIndexManager;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        if (
            $event->getCommand() instanceof UpdateSchemaDoctrineCommand
            && Configuration::DEFAULT_ENGINE == $this->container->getParameter('oro_search.engine')
        ) {
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
