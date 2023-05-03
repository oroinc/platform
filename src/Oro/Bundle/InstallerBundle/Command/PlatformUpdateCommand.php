<?php
declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\InstallerBundle\InstallerEvent;
use Oro\Bundle\InstallerBundle\InstallerEvents;
use Oro\Bundle\InstallerBundle\PlatformUpdateCheckerInterface;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand;
use Oro\Bundle\SecurityBundle\Command\LoadPermissionConfigurationCommand;
use Oro\Bundle\TranslationBundle\Command\OroTranslationUpdateCommand;
use Oro\Component\PhpUtils\PhpIniUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Updates the application state.
 */
class PlatformUpdateCommand extends AbstractCommand
{
    /** @var string */
    protected static $defaultName = 'oro:platform:update';

    private PlatformUpdateCheckerInterface $platformUpdateChecker;
    private ConfigManager $configManager;
    private ManagerRegistry $doctrine;

    public function __construct(
        ContainerInterface $container,
        PlatformUpdateCheckerInterface $platformUpdateChecker,
        ConfigManager $configManager,
        ManagerRegistry $doctrine
    ) {
        parent::__construct($container);

        $this->platformUpdateChecker = $platformUpdateChecker;
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
    }

    protected function configure()
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force the execution')
            ->addOption('skip-download-translations', null, InputOption::VALUE_NONE, 'Skip downloading translations')
            ->addOption('skip-translations', null, InputOption::VALUE_NONE, 'Skip applying translations')
            ->setDescription('Updates the application state.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command executes the application update commands
to update the application state and to (re-)build the application assets.

  <info>php %command.full_name%</info>

The <info>--force</info> option is just a safety switch. The command will exit after checking
the system requirements if this option is not used.

  <info>php %command.full_name% --force</info>

The <info>--skip-download-translations</info> and <info>--skip-translations</info> options can be used
to skip the step of downloading translations (already downloaded translations
will be applied if present), or skip applying the translations completely:

  <info>php %command.full_name% --force --skip-download-translations</info>
  <info>php %command.full_name% --force --skip-translations</info>

HELP
            )
            ->addUsage('--force')
            ->addUsage('--force --skip-download-translations')
            ->addUsage('--force --skip-translations')
        ;

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($this->getContainer()->getParameter('kernel.environment') === 'test') {
            $this->presetTestEnvironmentOptions($input, $output);
        }
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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
                $eventDispatcher->dispatch($event, InstallerEvents::INSTALLER_BEFORE_DATABASE_PREPARATION);
                $this->loadDataStep($commandExecutor, $output);
                $eventDispatcher->dispatch($event, InstallerEvents::INSTALLER_AFTER_DATABASE_PREPARATION);

                $this->finalStep($commandExecutor, $output, $input);

                $eventDispatcher->dispatch($event, InstallerEvents::FINISH);
            } catch (\Exception $exception) {
                $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
                // Exceptions may originate in the command executor and in PlatformUpdateCommand code itself
                return (0 != $commandExecutor->getLastCommandExitCode())
                    ? $commandExecutor->getLastCommandExitCode()
                    : 1;
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

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    protected function loadDataStep(CommandExecutor $commandExecutor, OutputInterface $output): self
    {
        ['formatting_code' => $formattingCode, 'language_code' => $languageCode] =
            $this->getDefaultFormattingCodeAndLanguageCode();

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
            ->runCommand('oro:cron:definitions:load', ['--process-isolation' => true])
            ->runCommand('oro:workflow:definitions:load', ['--process-isolation' => true])
            ->runCommand('oro:process:configuration:load', ['--process-isolation' => true])
            ->runCommand(
                LoadDataFixturesCommand::getDefaultName(),
                [
                    '--process-isolation' => true,
                    '--formatting-code' => $formattingCode,
                    '--language' => $languageCode,
                ]
            )
            ->runCommand('router:cache:clear', ['--process-isolation' => true])
            ->runCommand('oro:message-queue:create-queues', ['--process-isolation' => true])
        ;

        return $this;
    }

    /**
     * @return array ['formatting_code' => string, 'language_code' => string]
     */
    protected function getDefaultFormattingCodeAndLanguageCode(): array
    {
        $defaultLocalizationId = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION),
        );

        // Loading localization data without warming Doctrine metadata, as warming it too early
        // may break entity extended functionality for some entities and produce exceptions similar to
        // "Extend entity Oro\Bundle\OrganizationBundle\Entity\Organization autoloaded before initialization."
        $sql = 'SELECT loc.formatting_code AS formatting_code, lang.code AS language_code'
            . ' FROM oro_localization AS loc'
            . ' INNER JOIN oro_language AS lang ON lang.id = loc.language_id'
            . ' WHERE loc.id = :localizationId'
        ;
        /** @var Connection $conn */
        $conn = $this->doctrine->getManagerForClass(Localization::class)->getConnection();
        $stmt = $conn->executeQuery(
            $sql,
            ['localizationId' => $defaultLocalizationId],
            ['localizationId' => ParameterType::INTEGER]
        );
        return $stmt->fetchAssociative();
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    protected function finalStep(
        CommandExecutor $commandExecutor,
        OutputInterface $output,
        InputInterface $input,
    ): self {
        $this->processTranslations($input, $commandExecutor);

        $commandExecutor->runCommand('fos:js-routing:dump', ['--process-isolation' => true]);
        $commandExecutor->runCommand('oro:translation:dump', ['--process-isolation' => true]);

        return $this;
    }

    protected function checkRequirements(CommandExecutor $commandExecutor): int
    {
        $commandExecutor->runCommand('oro:check-requirements', ['--ignore-errors' => true, '--verbose' => 1]);

        return $commandExecutor->getLastCommandExitCode();
    }

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

    protected function checkSuggestedMemory(OutputInterface $output): void
    {
        $minimalSuggestedMemory = 1 * pow(1024, 3);
        $memoryLimit = PhpIniUtil::parseBytes(ini_get('memory_limit'));
        if ($memoryLimit !== -1.0 && $memoryLimit < $minimalSuggestedMemory) {
            $output->writeln('<comment>It\'s recommended at least 1Gb to be available for PHP CLI</comment>');
        }
    }

    protected function processTranslations(InputInterface $input, CommandExecutor $commandExecutor): void
    {
        if (!$input->getOption('skip-translations')) {
            if (!$input->getOption('skip-download-translations')) {
                $commandExecutor
                    ->runCommand(
                        OroTranslationUpdateCommand::getDefaultName(),
                        ['--process-isolation' => true, '--all' => true]
                    );
            }
            $commandExecutor
                ->runCommand('oro:translation:load', ['--process-isolation' => true, '--rebuild-cache' => true]);
        }
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->getContainer()->get('event_dispatcher');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function presetTestEnvironmentOptions(InputInterface $input, OutputInterface $output): void
    {
        $testEnvDefaultOptionValuesMap = [
            'force'             => true,
            'skip-translations' => true,
            'timeout'           => 600
        ];

        foreach ($testEnvDefaultOptionValuesMap as $optionName => $optionValue) {
            if ($input->hasParameterOption('--' . $optionName)) {
                continue;
            }

            $input->setOption($optionName, $optionValue);
        }

        $input->setInteractive(false);
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
    }
}
