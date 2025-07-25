<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Command;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates configuration data for entities.
 * Command uses config loader that utilizes DBAL based update approach.
 */
#[AsCommand(
    name: 'oro:entity-config:update',
    description: 'Updates configuration data for entities.'
)]
class UpdateCommand extends Command
{
    private ConfigLoader $configLoader;

    public function __construct(ConfigLoader $configLoader)
    {
        parent::__construct();

        $this->configLoader = $configLoader;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    public function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command reads entities configuration 
from their classes annotations and merges them into DB.
  !!! For internal use only

HELP
            )
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Update configuration data for entities');

        /** @var ConfigLoader $loader */
        $this->configLoader->load();

        return Command::SUCCESS;
    }
}
