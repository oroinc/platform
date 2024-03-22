<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Command;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates configuration data for entities.
 * Command uses config loader that utilizes DBAL based update approach.
 */
class UpdateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity-config:update';

    private ConfigLoader $configLoader;

    public function __construct(ConfigLoader $configLoader)
    {
        parent::__construct();

        $this->configLoader = $configLoader;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->setDescription('Updates configuration data for entities.')
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
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Update configuration data for entities');

        /** @var ConfigLoader $loader */
        $this->configLoader->load();

        return Command::SUCCESS;
    }
}
