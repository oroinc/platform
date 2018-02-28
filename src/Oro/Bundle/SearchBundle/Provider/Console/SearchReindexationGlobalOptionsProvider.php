<?php

namespace Oro\Bundle\SearchBundle\Provider\Console;

use Oro\Bundle\InstallerBundle\Command\PlatformUpdateCommand;
use Oro\Bundle\PlatformBundle\Command\HelpCommand;
use Oro\Bundle\PlatformBundle\Provider\Console\AbstractGlobalOptionsProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Adds two new parameters to `oro:platform:update` command allowing skip or postpone full re-indexation of search index
 * during application update process
 */
class SearchReindexationGlobalOptionsProvider extends AbstractGlobalOptionsProvider
{
    const SKIP_REINDEXATION_OPTION_NAME = 'skip-search-reindexation';
    const SCHEDULE_REINDEXATION_OPTION_NAME = 'schedule-search-reindexation';

    /**
     * {@inheritdoc}
     */
    public function addGlobalOptions(Command $command)
    {
        $name = ($command instanceof HelpCommand && $command->getCommand()) ?
            $command->getCommand()->getName() :
            $command->getName();

        if ($name !== PlatformUpdateCommand::NAME) {
            return;
        }

        $options = [
            new InputOption(
                self::SKIP_REINDEXATION_OPTION_NAME,
                null,
                InputOption::VALUE_NONE,
                'Determines whether search data reindexation need to be triggered or not'
            ),
            new InputOption(
                self::SCHEDULE_REINDEXATION_OPTION_NAME,
                null,
                InputOption::VALUE_NONE,
                'Determines whether search data reindexation need to be scheduled or not'
            )
        ];

        $this->addOptionsToCommand($command, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveGlobalOptions(InputInterface $input)
    {
    }
}
