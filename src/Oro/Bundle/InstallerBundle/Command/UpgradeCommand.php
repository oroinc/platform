<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeCommand extends AbstractCommand
{
    const COMMAND_NAME = 'oro:platform:upgrade20';

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription('Execute platform application upgrade commands. Upgrade from 1.10/1.12 to 2.0')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Forces operation to be executed.'
            )
            ->addOption(
                'skip-assets',
                null,
                InputOption::VALUE_NONE,
                'Skip UI related commands during update'
            )
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlinks the assets instead of copying it');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');

        if ($force) {
            $commandExecutor = $this->getCommandExecutor($input, $output);
            $commandExecutor->runCommand(
                'oro:platform:upgrade20:db-configs',
                [
                    '--process-isolation' => true,
                    '--force'             => true,
                    '--disabled-listeners' => 'all'
                ]
            );
            $commandExecutor->runCommand('cache:clear', [
                '--process-isolation' => true,
                '--disabled-listeners' => 'all'
            ]);
            $updateParams = [];
            foreach ($input->getOptions() as $key => $value) {
                if ($value) {
                    $updateParams['--' . $key] = $value;
                }
            }
            $commandExecutor->runCommand('oro:platform:update', $updateParams);
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
