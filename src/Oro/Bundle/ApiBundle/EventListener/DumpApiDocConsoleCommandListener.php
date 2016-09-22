<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Formatter\AbstractFormatter;

use Oro\Bundle\ApiBundle\ApiDoc\HtmlFormatter;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;

/**
 * This listener registers the "view" option for "api:doc:dump" and "api:swagger:dump" commands
 * and sets the view to the "oro_api.rest.doc_view_detector" service that is used to
 * get REST API documentation view by services depended on it.
 * Additionally, it sets dump context to true for HtmlFormatter
 */
class DumpApiDocConsoleCommandListener
{
    const VIEW_OPTION = 'view';

    /** @var RestDocViewDetector */
    protected $docViewDetector;

    /** @var AbstractFormatter */
    protected $htmlFormatter;

    /**
     * @param RestDocViewDetector $docViewDetector
     * @param AbstractFormatter   $htmlFormatter
     */
    public function __construct(RestDocViewDetector $docViewDetector, AbstractFormatter $htmlFormatter)
    {
        $this->docViewDetector = $docViewDetector;
        $this->htmlFormatter = $htmlFormatter;
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
            // sets dump context to true to say that now we are at the dump process
            if ($this->htmlFormatter instanceof HtmlFormatter) {
                $this->htmlFormatter->setIsDumpContext(true);
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
        if (!$innerCommand) {
            $innerCommandName = $this->getApiDocDumpCommandFromParameterOptions($input);
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
        return in_array($command->getName(), $this->getApiDocDumpCommands(), true);
    }

    /**
     * @param InputInterface $input
     *
     * @return string|null
     */
    protected function getApiDocDumpCommandFromParameterOptions(InputInterface $input)
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
    protected function getApiDocDumpCommands()
    {
        return ['api:doc:dump', 'api:swagger:dump'];
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
