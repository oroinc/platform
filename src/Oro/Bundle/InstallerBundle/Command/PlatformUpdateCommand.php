<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\InstallerBundle\InstallerEvent;
use Oro\Bundle\InstallerBundle\InstallerEvents;
use Oro\Bundle\InstallerBundle\PlatformUpdateCheckerInterface;
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
    /** @var string */
    protected static $defaultName = 'oro:platform:update';

    /** @var PlatformUpdateCheckerInterface */
    private $platformUpdateChecker;

    /**
     * @param PlatformUpdateCheckerInterface $platformUpdateChecker
     */
    public function __construct(PlatformUpdateCheckerInterface $platformUpdateChecker)
    {
        parent::__construct();
        $this->platformUpdateChecker = $platformUpdateChecker;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Execute platform application update commands and init platform assets.')
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
            if (!$this->checkReadyToUpdate($output)) {
                return 1;
            }

            $eventDispatcher = $this->getEventDispatcher();
            $event = new InstallerEvent($this, $input, $output, $commandExecutor);

            try {
                $eventDispatcher->dispatch(InstallerEvents::INSTALLER_BEFORE_DATABASE_PREPARATION, $event);
                $this->loadDataStep($commandExecutor, $output);
                $eventDispatcher->dispatch(InstallerEvents::INSTALLER_AFTER_DATABASE_PREPARATION, $event);

                $this->finalStep($commandExecutor, $output, $input, $input->getOption('skip-assets'));
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

            if (!$this->checkReadyToUpdate($output)) {
                return 1;
            }
        }

        return 0;
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
            ->runCommand(LoadPermissionConfigurationCommand::getDefaultName(), ['--process-isolation' => true])
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
                ->runCommand('fos:js-routing:dump', ['--process-isolation' => true])
                ->runCommand('oro:localization:dump', ['--process-isolation' => true])
                ->runCommand('oro:translation:dump', ['--process-isolation' => true])
                ->runCommand('oro:assets:build', ['--npm-install' => true]);
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
     *
     * @return bool
     */
    private function checkReadyToUpdate(OutputInterface $output): bool
    {
        $messages = $this->platformUpdateChecker->checkReadyToUpdate();
        if (!$messages) {
            return true;
        }

        $output->writeln('');
        $output->writeln('<error>The application update is not possible:</error>');
        foreach ($messages as $message) {
            $output->writeln(sprintf('<error>  - %s</error>', $message));
        }
        $output->writeln('');

        return false;
    }

    /**
     * @param OutputInterface $output
     */
    protected function checkSuggestedMemory(OutputInterface $output)
    {
        $minimalSuggestedMemory = 1 * pow(1024, 3);
        $memoryLimit = PhpIniUtil::parseBytes(ini_get('memory_limit'));
        if ($memoryLimit !== -1.0 && $memoryLimit < $minimalSuggestedMemory) {
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
                    ->runCommand(
                        OroLanguageUpdateCommand::getDefaultName(),
                        ['--process-isolation' => true, '--all' => true]
                    );
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
