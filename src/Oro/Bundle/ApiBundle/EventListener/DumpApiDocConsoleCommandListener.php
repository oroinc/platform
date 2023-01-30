<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * This listener registers the "view" option for "api:doc:dump" and "api:swagger:dump" commands
 * and sets the view to the "oro_api.rest.doc_view_detector" service that is used to
 * get REST API documentation view by services depended on it.
 */
class DumpApiDocConsoleCommandListener
{
    private const VIEW_OPTION = 'view';

    private RestDocViewDetector $docViewDetector;

    public function __construct(RestDocViewDetector $docViewDetector)
    {
        $this->docViewDetector = $docViewDetector;
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if ($command instanceof HelpCommand) {
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

    private function getHelpInnerCommand(Command $helpCommand, InputInterface $input): ?Command
    {
        $innerCommand = null;
        $commandProperty = ReflectionUtil::getProperty(new \ReflectionClass($helpCommand), 'command');
        if (null !== $commandProperty) {
            $commandProperty->setAccessible(true);
            $innerCommand = $commandProperty->getValue($helpCommand);
        }
        if (!$innerCommand) {
            $innerCommandName = $this->getApiDocDumpCommandFromParameterOptions($input);
            if ($innerCommandName && $helpCommand->getApplication()->has($innerCommandName)) {
                $innerCommand = $helpCommand->getApplication()->find($innerCommandName);
            }
        }

        return $innerCommand;
    }

    private function isApiDocDumpCommand(Command $command): bool
    {
        return \in_array($command->getName(), $this->getApiDocDumpCommands(), true);
    }

    private function getApiDocDumpCommandFromParameterOptions(InputInterface $input): ?string
    {
        $commands = $this->getApiDocDumpCommands();
        foreach ($commands as $command) {
            if (false !== $input->getParameterOption($command)) {
                return $command;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function getApiDocDumpCommands(): array
    {
        return ['api:doc:dump', 'api:swagger:dump'];
    }

    private function ensureViewOptionDefined(Command $command): void
    {
        $viewOption = new InputOption(
            self::VIEW_OPTION,
            null,
            InputOption::VALUE_OPTIONAL,
            'A view for which API definitions should be dumped.',
            ApiDoc::DEFAULT_VIEW
        );
        $inputDefinition = $command->getApplication()->getDefinition();
        if (!$inputDefinition->hasOption(self::VIEW_OPTION)) {
            $inputDefinition->addOption($viewOption);
        }
        $commandDefinition = $command->getDefinition();
        if (!$commandDefinition->hasOption(self::VIEW_OPTION)) {
            $commandDefinition->addOption($viewOption);
        }
    }
}
