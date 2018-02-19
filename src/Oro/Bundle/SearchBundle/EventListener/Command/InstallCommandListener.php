<?php

namespace Oro\Bundle\SearchBundle\EventListener\Command;

use Oro\Bundle\InstallerBundle\Command\InstallCommand;
use Oro\Bundle\InstallerBundle\InstallerEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Will execute full search index re-indexation when database ready to work on installation process of application
 */
class InstallCommandListener
{
    use ReindexationCommandTrait;

    /** @var RequestStack */
    protected $requestStack;

    /** @var string */
    protected $reindexCommandName;

    /** @var bool */
    protected $isScheduled;

    /**
     * @param RequestStack $requestStack
     * @param string $reindexCommandName
     * @param bool $isScheduled
     */
    public function __construct(RequestStack $requestStack, string $reindexCommandName, bool $isScheduled = false)
    {
        $this->requestStack = $requestStack;
        $this->reindexCommandName = $reindexCommandName;
        $this->isScheduled = $isScheduled;
    }

    /**
     * @param InstallerEvent $event
     */
    public function onAfterDatabasePreparation(InstallerEvent $event)
    {
        if (!$this->isApplicable($event->getCommand())) {
            return;
        }

        $output = $event->getOutput();

        $output->writeln(
            sprintf('<comment>Running full re-indexation with "%s" command</comment>', $this->reindexCommandName)
        );
        $this->executeReindexation(
            $event,
            $this->reindexCommandName,
            $this->isScheduled,
            !$this->requestStack->getMasterRequest() instanceof Request
        );
        $output->writeln('');
    }

    /**
     * @param Command $command
     * @return bool
     */
    protected function isApplicable(Command $command): bool
    {
        return $command->getName() === InstallCommand::NAME;
    }
}
