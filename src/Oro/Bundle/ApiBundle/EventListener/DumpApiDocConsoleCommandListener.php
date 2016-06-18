<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;

/**
 * This listener registers the "view" option for "api:doc:dump" and "api:swagger:dump" commands
 * and sets the view to the "oro_api.rest.doc_view_detector" service that is used to
 * get REST API documentation view by services depended on it.
 */
class DumpApiDocConsoleCommandListener
{
    const VIEW_OPTION = 'view';

    /** @var RestDocViewDetector */
    protected $docViewDetector;

    /**
     * @param RestDocViewDetector $docViewDetector
     */
    public function __construct(RestDocViewDetector $docViewDetector)
    {
        $this->docViewDetector = $docViewDetector;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();
        if ('help' === $command->getName()) {
            $innerCommand = $this->getHelpInnerCommand($command, $event->getInput());
            if ($innerCommand && $this->isApiDocDumpCommand($innerCommand)) {
                $this->ensureViewOptionDefined($innerCommand);
            }
        } elseif ($this->isApiDocDumpCommand($command)) {
            $this->ensureViewOptionDefined($command);
            $view = $event->getInput()->getParameterOption('--' . self::VIEW_OPTION);
            if ($view) {
                $this->docViewDetector->setView($view);
            }
        }
    }

    /**
     * @param Command        $helpCommand
     * @param InputInterface $input
     *
     * @return Command|null
     */
    protected function getHelpInnerCommand(Command $helpCommand, InputInterface $input)
    {
        $innerCommand = null;
        $reflClass = new \ReflectionClass($helpCommand);
        if ($reflClass->hasProperty('command')) {
            $property = $reflClass->getProperty('command');
            $property->setAccessible(true);
            $innerCommand = $property->getValue($helpCommand);
        }
        if (!$innerCommand && $input->hasArgument('command_name')) {
            $innerCommandName = $input->getArgument('command_name');
            if ($innerCommandName && $helpCommand->getApplication()->has($innerCommandName)) {
                $innerCommand = $helpCommand->getApplication()->find($innerCommandName);
            }
        }

        return $innerCommand;
    }

    /**
     * @param Command $command
     *
     * @return bool
     */
    protected function isApiDocDumpCommand(Command $command)
    {
        return in_array($command->getName(), ['api:doc:dump', 'api:swagger:dump'], true);
    }

    /**
     * @param Command $command
     */
    protected function ensureViewOptionDefined(Command $command)
    {
        $inputDefinition = $command->getApplication()->getDefinition();
        if (!$inputDefinition->hasOption(self::VIEW_OPTION)) {
            $inputDefinition->addOption(
                new InputOption(
                    self::VIEW_OPTION,
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'A view for which API definitions should be dumped.',
                    ApiDoc::DEFAULT_VIEW
                )
            );
        }
    }
}
