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
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlinks the assets instead of copying it')
            ->addOption(
                'skip-translations',
                null,
                InputOption::VALUE_NONE,
                'Determines whether translation data need to be loaded or not'
            )
            ->addOption(
                'skip-download-translations',
                null,
                InputOption::VALUE_NONE,
                'Determines whether translation data need to be downloaded or not'
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
            $commandExecutor = $this->getCommandExecutor($input, $output);

            try {
                $commandExecutor->runCommand('oro:platform:upgrade20:db-configs', ['--force' => true]);

                $commandExecutor->runCommand(
                    'cache:warmup',
                    ['--no-optional-warmers' => true, '--process-isolation' => true]
                );

                $updateParams = ['--process-isolation' => true];
                foreach ($input->getOptions() as $key => $value) {
                    if ($value !== '' && $value !== null) {
                        $updateParams['--' . $key] = $value;
                    }
                }

                if ($input->getOption('skip-assets')) {
                    $updateParams['--skip-assets'] = true;
                }

                if ($input->getOption('skip-translations')) {
                    $updateParams['--skip-translations'] = true;

                    if ($input->getOption('skip-download-translations')) {
                        $updateParams['--skip-download-translations'] = true;
                    }
                }

                $commandExecutor->runCommand('oro:platform:update', $updateParams);

                return 0;
            } catch (\Exception $exception) {
                return $commandExecutor->getLastCommandExitCode();
            }
        } else {
            $output->writeln(
                '<comment>ATTENTION</comment>: Database backup is highly recommended before executing this command.'
            );
            $output->writeln('           Please, remove application cache before run this command.');
            $output->writeln('');
            $output->writeln('To force execution run command with <info>--force</info> option:');
            $output->writeln(sprintf('    <info>%s --force</info>', $this->getName()));

            return 0;
        }
    }
}
