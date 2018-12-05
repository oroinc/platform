<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\InstallerBundle\InstallerEvent;
use Oro\Bundle\InstallerBundle\InstallerEvents;
use Oro\Bundle\SecurityBundle\Command\LoadConfigurablePermissionCommand;
use Oro\Bundle\SecurityBundle\Command\LoadPermissionConfigurationCommand;
use Oro\Bundle\TranslationBundle\Command\OroLanguageUpdateCommand;
use Oro\Component\PhpUtils\PhpIniUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Updates application to actual state that the corresponding to local code base
 */
class PlatformUpdateCommand extends AbstractCommand
{
    const NAME = 'oro:platform:update';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Execute platform application update commands and init platform assets.')
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandExecutor = $this->getCommandExecutor($input, $output);

        $this->checkSuggestedMemory($output);

        $exitCode = $this->checkRequirements($commandExecutor);
        if ($exitCode > 0) {
            return $exitCode;
        }

        $force = $input->getOption('force');

        if ($force) {
            $eventDispatcher = $this->getEventDispatcher();
            $event = new InstallerEvent($this, $input, $output, $commandExecutor);

            try {
                $eventDispatcher->dispatch(InstallerEvents::INSTALLER_BEFORE_DATABASE_PREPARATION, $event);
                $this->loadDataStep($commandExecutor, $output);
                $eventDispatcher->dispatch(InstallerEvents::INSTALLER_AFTER_DATABASE_PREPARATION, $event);

                $skipAssets = $input->getOption('skip-assets');

                $this->finalStep($commandExecutor, $output, $input, $skipAssets);

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

    /**
     * @param CommandExecutor $commandExecutor
     * @param OutputInterface $output
     *
     * @return $this
     */
    protected function loadDataStep(CommandExecutor $commandExecutor, OutputInterface $output)
    {
        $commandExecutor
            ->runCommand(
                'oro:migration:load',
                [
                    '--process-isolation' => true,
                    '--force'             => true,
                    '--timeout'           => $commandExecutor->getDefaultOption('process-timeout')
                ]
            )
            ->runCommand(LoadPermissionConfigurationCommand::NAME, ['--process-isolation' => true])
            ->runCommand(LoadConfigurablePermissionCommand::NAME, ['--process-isolation' => true])
            ->runCommand(
                'oro:cron:definitions:load',
                [
                    '--process-isolation' => true
                ]
            )
            ->runCommand(
                'oro:workflow:definitions:load',
                ['--process-isolation' => true]
            )
            ->runCommand('oro:process:configuration:load', ['--process-isolation' => true])
            ->runCommand('oro:migration:data:load', ['--process-isolation' => true])
            ->runCommand('router:cache:clear', ['--process-isolation' => true])
            ->runCommand('oro:message-queue:create-queues', ['--process-isolation' => true])
        ;

        return $this;
    }

    /**
     * @param CommandExecutor $commandExecutor
     * @param OutputInterface $output
     * @param InputInterface $input
     * @param boolean $skipAssets
     *
     * @return $this
     */
    protected function finalStep(
        CommandExecutor $commandExecutor,
        OutputInterface $output,
        InputInterface $input,
        $skipAssets
    ) {
        $this->processTranslations($input, $commandExecutor);

        if (!$skipAssets) {
            $assetsOptions = [];
            if ($input->hasOption('symlink') && $input->getOption('symlink')) {
                $assetsOptions['--symlink'] = true;
            }

            $commandExecutor
                ->runCommand('assets:install', $assetsOptions)
                ->runCommand('oro:assets:build', ['--npm-install' => true])
                ->runCommand('fos:js-routing:dump', ['--process-isolation' => true])
                ->runCommand('oro:localization:dump', ['--process-isolation' => true])
                ->runCommand('oro:translation:dump', ['--process-isolation' => true])
                ->runCommand(
                    'oro:requirejs:build',
                    ['--ignore-errors' => true, '--process-isolation' => true]
                );
        }

        return $this;
    }

    /**
     * @param CommandExecutor $commandExecutor
     *
     * @return int
     */
    protected function checkRequirements(CommandExecutor $commandExecutor)
    {
        $commandExecutor->runCommand(
            'oro:check-requirements',
            ['--ignore-errors' => true, '--verbose' => 1]
        );

        return $commandExecutor->getLastCommandExitCode();
    }

    /**
     * @param OutputInterface $output
     */
    protected function checkSuggestedMemory(OutputInterface $output)
    {
        $minimalSuggestedMemory = 1 * pow(1024, 3);
        $memoryLimit = PhpIniUtil::parseBytes(ini_get('memory_limit'));
        if ($memoryLimit !== -1 && $memoryLimit < $minimalSuggestedMemory) {
            $output->writeln('<comment>It\'s recommended at least 1Gb to be available for PHP CLI</comment>');
        }
    }

    /**
     * @param InputInterface $input
     * @param CommandExecutor $commandExecutor
     */
    protected function processTranslations(InputInterface $input, CommandExecutor $commandExecutor)
    {
        if (!$input->getOption('skip-translations')) {
            if (!$input->getOption('skip-download-translations')) {
                $commandExecutor
                    ->runCommand(OroLanguageUpdateCommand::NAME, ['--process-isolation' => true, '--all' => true]);
            }
            $commandExecutor
                ->runCommand('oro:translation:load', ['--process-isolation' => true, '--rebuild-cache' => true]);
        }
    }

    /**
     * @return EventDispatcherInterface
     */
    private function getEventDispatcher()
    {
        return $this->getContainer()->get('event_dispatcher');
    }
}
