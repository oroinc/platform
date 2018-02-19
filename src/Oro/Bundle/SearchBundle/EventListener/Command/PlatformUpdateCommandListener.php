<?php

namespace Oro\Bundle\SearchBundle\EventListener\Command;

use Oro\Bundle\InstallerBundle\Command\PlatformUpdateCommand;
use Oro\Bundle\InstallerBundle\InstallerEvent;
use Oro\Bundle\SearchBundle\Provider\Console\SearchReindexationGlobalOptionsProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Will execute full search index re-indexation when database ready to work on update process of application
 */
class PlatformUpdateCommandListener
{
    use ReindexationCommandTrait;

    /** @var string */
    protected $reindexCommandName;

    /**
     * @param string $reindexCommandName
     */
    public function __construct(string $reindexCommandName)
    {
        $this->reindexCommandName = $reindexCommandName;
    }

    /**
     * @param InstallerEvent $event
     */
    public function onAfterDatabasePreparation(InstallerEvent $event)
    {
        if (!$this->isApplicable($event->getCommand())) {
            return;
        }

        $input = $event->getInput();
        $result = 'skipped';

        if (!$this->skipReindexation($input)) {
            $scheduled = $this->scheduleReindexation($input);

            $this->executeReindexation($event, $this->reindexCommandName, $scheduled);

            $result = $scheduled ? 'scheduled' : 'finished';
        }

        $event->getOutput()->writeln([
            sprintf(
                '<comment>Full re-indexation with "%s" command %s</comment>',
                $this->reindexCommandName,
                $result
            ),
            ''
        ]);
    }

    /**
     * @param Command $command
     * @return bool
     */
    protected function isApplicable(Command $command): bool
    {
        return $command->getName() === PlatformUpdateCommand::NAME;
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    private function skipReindexation(InputInterface $input): bool
    {
        return $this->isOptionSet($input, SearchReindexationGlobalOptionsProvider::SKIP_REINDEXATION_OPTION_NAME);
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    private function scheduleReindexation(InputInterface $input): bool
    {
        return $this->isOptionSet($input, SearchReindexationGlobalOptionsProvider::SCHEDULE_REINDEXATION_OPTION_NAME);
    }

    /**
     * @param InputInterface $input
     * @param string $optionName
     * @return bool
     */
    private function isOptionSet(InputInterface $input, string $optionName): bool
    {
        return $input->hasOption($optionName) && $input->getOption($optionName);
    }
}
