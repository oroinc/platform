<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigUpgradeCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:platform:upgrade20:db-configs';

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription('Execute migration config stored in database to perform upgrade from 1.12 to 2.0.')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Forces operation to be executed.'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');

        if ($force) {
            try {
                $this->getContainer()->get('oro_installer.namespace_migration')->migrate();
                $this->getContainer()->get('oro_entity_config.config_manager')->clear();
            } catch (\InvalidArgumentException $e) {
                $output->writeln('Nothing to update in configs');
            }
        } else {
            $output->writeln(
                '<comment>ATTENTION</comment>: Database backup is highly recommended before executing this command.'
            );
            $output->writeln('           Please, remove application cache before run this command.');
            $output->writeln('');
            $output->writeln('To force execution run command with <info>--force</info> option:');
            $output->writeln(sprintf('    <info>%s --force</info>', $this->getName()));
        }
    }
}
