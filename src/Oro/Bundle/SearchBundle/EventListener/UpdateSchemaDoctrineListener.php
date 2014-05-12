<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand;

use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;

use Oro\Bundle\SearchBundle\Command\AddFulltextIndexesCommand;

class UpdateSchemaDoctrineListener
{
    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $command = $event->getCommand();
        if ($event->getCommand() instanceof UpdateSchemaDoctrineCommand) {
            $output = $event->getOutput();
            $input  = $event->getInput();

            if ($input->getOption('force')) {
                $application = $command->getApplication();
                $arguments   = [
                    'command' => AddFulltextIndexesCommand::COMMAND_NAME
                ];
                $indexInput  = new ArrayInput($arguments);
                $returnCode  = $application->run($indexInput, $output);

                if ($returnCode == 0) {
                    $output->writeln('Schema update and create index completed');
                }
            }
        }
    }
}
