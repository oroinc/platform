<?php
declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\InstallerBundle\Command\Provider\InputOptionProvider;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\InstallerBundle\InstallerEvent;
use Oro\Bundle\InstallerBundle\InstallerEvents;
use Oro\Bundle\InstallerBundle\ScriptExecutor;
use Oro\Bundle\InstallerBundle\ScriptManager;
use Oro\Bundle\LocaleBundle\Command\LocalizationOptionsCommandTrait;
use Oro\Bundle\LocaleBundle\Command\UpdateLocalizationCommand;
use Oro\Bundle\LocaleBundle\DependencyInjection\OroLocaleExtension;
use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand;
use Oro\Bundle\SecurityBundle\Command\LoadPermissionConfigurationCommand;
use Oro\Bundle\TranslationBundle\Command\OroTranslationUpdateCommand;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Constraints\Url;

/**
 * Application installer.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class InstallCommand extends AbstractCommand implements InstallCommandInterface
{
    use LocalizationOptionsCommandTrait;

    /** @var string */
    protected static $defaultName = 'oro:install';

    private Process $assetsCommandProcess;
    private InputOptionProvider $inputOptionProvider;
    private ApplicationState $applicationState;
    private ScriptManager $scriptManager;
    private ManagerRegistry $doctrine;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ContainerInterface $container,
        ManagerRegistry $doctrine,
        EventDispatcherInterface $eventDispatcher,
        ApplicationState $applicationState,
        ScriptManager $scriptManager
    ) {
        parent::__construct($container);

        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
        $this->applicationState = $applicationState;
        $this->scriptManager = $scriptManager;
    }

    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    protected function configure()
    {
        $this
            ->addOption('application-url', null, InputOption::VALUE_OPTIONAL, 'Application URL')
            ->addOption('organization-name', null, InputOption::VALUE_OPTIONAL, 'Organization name')
            ->addOption('user-name', null, InputOption::VALUE_OPTIONAL, 'Admin username')
            ->addOption('user-email', null, InputOption::VALUE_OPTIONAL, 'Admin user email')
            ->addOption('user-firstname', null, InputOption::VALUE_OPTIONAL, 'Admin user first name')
            ->addOption('user-lastname', null, InputOption::VALUE_OPTIONAL, 'Admin user last name')
            ->addOption('user-password', null, InputOption::VALUE_OPTIONAL, 'Admin user password')
            ->addOption('sample-data', null, InputOption::VALUE_OPTIONAL, 'Load sample data')
        ;
        $this->addLocalizationOptions();
        $this
            ->addOption('skip-download-translations', null, InputOption::VALUE_NONE, 'Skip downloading translations')
            ->addOption('skip-translations', null, InputOption::VALUE_NONE, 'Skip applying translations')
            ->addOption('drop-database', null, InputOption::VALUE_NONE, 'Delete all existing data')
            ->setDescription('Application installer.')
            // @codingStandardsIgnoreStart
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command is the application installer. It installs the application
with all schema and data migrations, prepares assets and application caches.

  <info>php %command.full_name%</info>

The <info>--application-url</info> option can be used to specify the URL at which
the management console (back-office) of the application will be available.
Please make sure that you web-server is configured properly (see more at
<comment>https://doc.oroinc.com/backend/setup/dev-environment/web-server-config/</comment>).

  <info>php %command.full_name% --application-url=<url></info>
  <info>php %command.full_name% --application-url='http://example.com/'</info>

It is also possible to modify the application URL after the installation:

  <info>php oro:config:update oro_ui.application_url 'http://example.com/'</info>

The <info>--organization-name</info> option can be used to specify your company name:

  <info>php %command.full_name% --organization-name=<company></info>
  <info>php %command.full_name% --organization-name="Acme Inc."</info>

The <info>--user-name</info>, <info>--user-email</info>, <info>--user-firstname</info>, <info>--user-lastname</info> and
<info>--user-password</info> options allow to configure the admin user account details:

  <info>php %command.full_name% --user-name=<username> --user-email=<email> --user-firstname=<firstname> --user-lastname=<lastname> --user-password=<password></info>

The <info>--sample-data</info> option can be used specify whether the demo sample data
should be loaded after the installation:

  <info>php %command.full_name% --sample-data=y</info>
  <info>php %command.full_name% --sample-data=n</info>
HELP
            . $this->getLocalizationOptionsHelp()
            . <<<'HELP'

The <info>--skip-download-translations</info> and <info>--skip-translations</info> options can be used
to skip the step of downloading translations (already downloaded translations
will be applied if present), or skip applying the translations completely:

  <info>php %command.full_name% --skip-download-translations</info>
  <info>php %command.full_name% --skip-translations</info>

The <info>--drop-database</info> option should be provided when reinstalling the application
from scratch on top of the existing database that needs to be wiped out first,
or otherwise the installation will fail:

  <info>php %command.full_name% --drop-database</info>

Please see below an example with the most commonly used options:

  <info>php %command.full_name% \
    -vvv \
    --env=prod \
    --timeout=600 \
    --language=en \
    --formatting-code=en_US \
    --organization-name='Acme Inc.' \
    --user-name=admin \
    --user-email=admin@example.com \
    --user-firstname=John \
    --user-lastname=Doe \
    --user-password='PleaseReplaceWithSomeStrongPassword' \
    --application-url='http://example.com/' \
    --sample-data=y</info>

Or, as a one-liner:

  <info>php %command.full_name% -vvv --env=prod --timeout=600 --language=en --formatting-code=en_US --organization-name='Acme Inc.' --user-name=admin --user-email=admin@example.com --user-firstname=John --user-lastname=Doe --user-password='PleaseReplaceWithSomeStrongPassword' --application-url='http://example.com/' --sample-data=y</info>

HELP
            )
            ->addUsage('--application-url=<url>')
            ->addUsage('--organization-name=<company>')
            ->addUsage('--user-name=<username> --user-email=<email> --user-firstname=<firstname> --user-lastname=<lastname> --user-password=<password>')
            ->addUsage('--sample-data=y')
            ->addUsage('--sample-data=n')
            ->addLocalizationOptionsUsage()
            ->addUsage('--skip-download-translations')
            ->addUsage('--skip-translations')
            ->addUsage('--drop-database')
            ->addUsage("-vvv --env=prod --timeout=600 --language=en --formatting-code=en_US --organization-name=<company> --user-name=<username> --user-email=<email> --user-firstname=<firstname> --user-lastname=<lastname> --user-password=<password> --application-url=<url> --sample-data=y")
            ->addUsage("-vvv --env=prod --timeout=600 --language=en --formatting-code=en_US --organization-name='Acme Inc.' --user-name=admin --user-email=admin@example.com --user-firstname=John --user-lastname=Doe --user-password='PleaseReplaceWithSomeStrongPassword' --application-url='http://example.com/' --sample-data=y")
            // @codingStandardsIgnoreEnd
        ;

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($this->isTestEnvironment()) {
            $this->presetTestEnvironmentOptions($input, $output);
        }
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->inputOptionProvider = new InputOptionProvider($output, $input, $this->getHelperSet()->get('question'));

        $this->validateApplicationUrl($input->getOption('application-url'));
        if (false === $input->isInteractive()) {
            $this->validate($input);
            $this->validateLocalizationOptions($input);
        }

        if ($this->isInstalled() && !$input->getOption('drop-database')) {
            $this->alreadyInstalledMessageShow($input, $output);

            // Using non-reserved exit code for already installed case.
            // See https://tldp.org/LDP/abs/html/exitcodes.html
            return 3;
        }

        $commandExecutor = $this->getCommandExecutor($input, $output);

        $output->writeln('<info>Installing Oro Application.</info>');
        $output->writeln('');

        $exitCode = $this->checkRequirements($commandExecutor);
        if ($exitCode > 0) {
            return $exitCode;
        }

        $event = new InstallerEvent($this, $input, $output, $commandExecutor);

        try {
            $this->prepareStep($input, $output);

            $this->eventDispatcher->dispatch($event, InstallerEvents::INSTALLER_BEFORE_DATABASE_PREPARATION);

            $this->loadDataStep($commandExecutor, $output);
            $this->eventDispatcher->dispatch($event, InstallerEvents::INSTALLER_AFTER_DATABASE_PREPARATION);

            $this->finalStep($commandExecutor, $output, $input);
            // cache clear must be done after assets build process finished,
            // otherwise, it could lead to unpredictable errors
            $this->clearCache($commandExecutor, $input);

            $this->eventDispatcher->dispatch($event, InstallerEvents::FINISH);
        } catch (\Exception $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
            // Exceptions may originate in the command executor and in InstallCommand code itself
            return (0 != $commandExecutor->getLastCommandExitCode()) ? $commandExecutor->getLastCommandExitCode() : 1;
        }

        $this->successfullyInstalledMessageShow($input, $output);

        return $buildAssetsProcessExitCode ?? 0;
    }

    private function alreadyInstalledMessageShow(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $io->error('An Oro application is already installed.');
        $io->text('To proceed with the installation:');
        $io->listing([
            'remove caches in <info>var/cache</info> folder manually,',
            'drop the database manually or reinstall with the <info>--drop-database</info> option.',
        ]);
        $io->warning([
            'All data will be lost. Database backup is highly recommended!'
        ]);
    }

    private function successfullyInstalledMessageShow(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln(
            sprintf(
                '<info>Oro Application has been successfully installed in <comment>%s</comment> mode.</info>',
                $input->getOption('env')
            )
        );
        if ('prod' != $input->getOption('env')) {
            $output->writeln(
                '<info>To run application in <comment>prod</comment> mode, ' .
                'please run <comment>cache:clear</comment> command with <comment>--env=prod</comment> parameter</info>'
            );
        }
        if ('prod' == $input->getOption('env')) {
            $output->writeln(
                '<info>Please run <comment>oro:api:doc:cache:clear</comment> command to warm-up ' .
                'API documentation cache</info>'
            );
        }
        $output->writeln(
            '<info>Ensure that at least one consumer service is running. ' .
            'Use the <comment>oro:message-queue:consume</comment> ' .
            'command to launch a consumer service instance. See ' .
            '<comment>' .
            'https://doc.oroinc.com/backend/setup/dev-environment/manual-installation/crm-ce/' .
            '#configure-and-run-required-background-processes' .
            '</comment> ' .
            'for more information.</info>'
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function validate(InputInterface $input)
    {
        $requiredParams = ['user-email', 'user-firstname', 'user-lastname', 'user-password'];
        $emptyParams    = [];

        foreach ($requiredParams as $param) {
            if (null === $input->getOption($param)) {
                $emptyParams[] = '--' . $param;
            }
        }

        if (!empty($emptyParams)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "The %s arguments are required in non-interactive mode",
                    implode(', ', $emptyParams)
                )
            );
        }
    }

    protected function checkRequirements(CommandExecutor $commandExecutor): int
    {
        $commandExecutor->runCommand('oro:check-requirements', ['--ignore-errors' => true, '--verbose' => 2]);

        return $commandExecutor->getLastCommandExitCode();
    }

    /**
     * Drop schema, clear entity config and extend caches
     */
    protected function prepareStep(InputInterface $input, OutputInterface $output): self
    {
        if ($input->getOption('drop-database')) {
            $output->writeln('<info>Drop schema.</info>');
            $managers = $this->doctrine->getManagers();
            foreach ($managers as $name => $manager) {
                if ($manager instanceof EntityManager) {
                    $tool = new SchemaTool($manager);
                    $tool->dropDatabase();
                }
            }
        }

        return $this;
    }

    protected function getNotBlankValidator(string $message): callable
    {
        return function ($value) use ($message) {
            if (strlen(trim($value)) === 0) {
                throw new \Exception($message);
            }

            return $value;
        };
    }

    /**
     * Update the administrator user
     */
    protected function updateUser(CommandExecutor $commandExecutor): void
    {
        $emailValidator     = $this->getNotBlankValidator('The email must be specified');
        $firstNameValidator = $this->getNotBlankValidator('The first name must be specified');
        $lastNameValidator  = $this->getNotBlankValidator('The last name must be specified');
        $passwordValidator  = function ($value) {
            if (strlen(trim($value)) < 2) {
                throw new \Exception('The password must be at least 2 characters long');
            }

            return $value;
        };

        $options = [
            'user-name'      => [
                'label'                  => 'Username',
                'options'                => [
                    'constructorArgs' => [LoadAdminUserData::DEFAULT_ADMIN_USERNAME]
                ],
                'defaultValue'           => LoadAdminUserData::DEFAULT_ADMIN_USERNAME,
            ],
            'user-email'     => [
                'label'                  => 'Email',
                'options'                => ['settings' => ['validator' => [$emailValidator]]],
                'defaultValue'           => null,
            ],
            'user-firstname' => [
                'label'                  => 'First name',
                'options'                => ['settings' => ['validator' => [$firstNameValidator]]],
                'defaultValue'           => null,
            ],
            'user-lastname'  => [
                'label'                  => 'Last name',
                'options'                => ['settings' => ['validator' => [$lastNameValidator]]],
                'defaultValue'           => null,
            ],
            'user-password'  => [
                'label'                  => 'Password',
                'options'                => ['settings' => ['validator' => [$passwordValidator], 'hidden' => [true]]],
                'defaultValue'           => null,
            ],
        ];

        $commandExecutor->runCommand(
            'oro:user:update',
            array_merge(
                [
                    'user-name'           => LoadAdminUserData::DEFAULT_ADMIN_USERNAME,
                    '--process-isolation' => true
                ],
                $this->inputOptionProvider->getCommandParametersFromOptions($options)
            )
        );
    }

    protected function updateOrganization(CommandExecutor $commandExecutor): void
    {
        /** @var ConfigManager $configManager */
        $configManager             = $this->getContainer()->get('oro_config.global');
        $defaultOrganizationName   = $configManager->get('oro_ui.organization_name');
        $organizationNameValidator = function ($value) use (&$defaultOrganizationName) {
            $len = strlen(trim($value));
            if ($len === 0 && empty($defaultOrganizationName)) {
                throw new \Exception('The organization name must not be empty');
            }
            if ($len > 15) {
                throw new \Exception('The organization name must be not more than 15 characters long');
            }
            return $value;
        };

        $options = [
            'organization-name' => [
                'label'                  => 'Organization name',
                'options'                => [
                    'constructorArgs' => [$defaultOrganizationName],
                    'settings' => ['validator' => [$organizationNameValidator]]
                ],
                'defaultValue'           => $defaultOrganizationName,
            ]
        ];

        $commandExecutor->runCommand(
            'oro:organization:update',
            array_merge(
                [
                    'organization-name' => 'default',
                    '--process-isolation' => true,
                ],
                $this->inputOptionProvider->getCommandParametersFromOptions($options)
            )
        );
    }

    /**
     * Update system settings such as app url, company name and short name
     */
    protected function updateSystemSettings(): void
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->getContainer()->get('oro_config.global');
        $options       = [
            'application-url' => [
                'label' => 'Application URL',
                'config_key' => 'oro_ui.application_url',
                'options' => [
                    'settings' => [
                        'validator' => [
                            function (?string $applicationUrl) {
                                if (!$applicationUrl) {
                                    throw new \InvalidArgumentException(
                                        'The value of the "application-url" parameter should not be blank.'
                                    );
                                }

                                $this->validateApplicationUrl($applicationUrl);

                                return $applicationUrl;
                            }
                        ]
                    ]
                ]
            ]
        ];

        foreach ($options as $optionName => $optionData) {
            $configKey    = $optionData['config_key'];
            $defaultValue = $configManager->get($configKey);

            $value = $this->inputOptionProvider->get(
                $optionName,
                $optionData['label'],
                $defaultValue,
                array_merge(['constructorArgs' => [$defaultValue]], $optionData['options'])
            );

            // update setting if it's not empty and not equal to default value
            if (!empty($value) && $value !== $defaultValue) {
                $configManager->set($configKey, $value);
            }
        }

        $configManager->flush();
    }

    protected function loadDataStep(CommandExecutor $commandExecutor, OutputInterface $output): self
    {
        $output->writeln('<info>Setting up database.</info>');
        $formattingCode = $this->getContainer()->getParameter(OroLocaleExtension::PARAMETER_FORMATTING_CODE);
        $language = $this->getContainer()->getParameter(OroLocaleExtension::PARAMETER_LANGUAGE);
        $commandExecutor
            ->runCommand(
                'oro:migration:load',
                [
                    '--force'             => true,
                    '--process-isolation' => true,
                    '--timeout'           => $commandExecutor->getDefaultOption('process-timeout'),
                ]
            )
            ->runCommand(LoadPermissionConfigurationCommand::getDefaultName(), ['--process-isolation' => true])
            ->runCommand('oro:cron:definitions:load', ['--process-isolation' => true])
            ->runCommand('oro:workflow:definitions:load', ['--process-isolation' => true])
            ->runCommand('oro:process:configuration:load', ['--process-isolation' => true])
            ->runCommand(
                LoadDataFixturesCommand::getDefaultName(),
                \array_merge(
                    [
                        '--process-isolation' => true,
                        '--no-interaction' => true,
                    ],
                    $this->getLocalizationParametersFromOptions($formattingCode, $language)
                )
            );

        $output->writeln('');
        $output->writeln('<info>Administration setup.</info>');

        $this->updateSystemSettings();
        $this->updateOrganization($commandExecutor);
        $this->updateUser($commandExecutor);
        $this->updateLocalization($commandExecutor);

        $isDemo = $this->inputOptionProvider->get(
            'sample-data',
            'Load sample data (y/n)',
            null,
            [
                'class' => ConfirmationQuestion::class,
                'constructorArgs' => [false]
            ]
        );
        if ($isDemo) {
            // load demo fixtures
            $commandExecutor->runCommand(
                'oro:migration:data:load',
                ['--process-isolation'  => true, '--fixtures-type' => 'demo']
            );
        }

        $output->writeln('');

        return $this;
    }

    protected function finalStep(
        CommandExecutor $commandExecutor,
        OutputInterface $output,
        InputInterface $input
    ): self {
        $output->writeln('<info>Preparing application.</info>');

        $this->processTranslations($input, $commandExecutor);

        // run installer scripts
        $this->processInstallerScripts($output, $commandExecutor);

        $this->applicationState->setInstalled();
        $commandExecutor->runCommand('fos:js-routing:dump', ['--process-isolation' => true]);
        $commandExecutor->runCommand('oro:translation:dump', ['--process-isolation' => true]);
        $output->writeln('');

        return $this;
    }

    protected function clearCache(CommandExecutor $commandExecutor, InputInterface $input): void
    {
        $cacheClearOptions = ['--process-isolation' => true];
        if ($commandExecutor->getDefaultOption('no-debug')) {
            $cacheClearOptions['--no-debug'] = true;
        }
        if ($input->getOption('env')) {
            $cacheClearOptions['--env'] = $input->getOption('env');
        }
        $commandExecutor->runCommand('cache:clear', $cacheClearOptions);
    }

    protected function processInstallerScripts(OutputInterface $output, CommandExecutor $commandExecutor): void
    {
        $scriptExecutor = new ScriptExecutor($output, $this->getContainer(), $commandExecutor);

        $scriptFiles   = $this->scriptManager->getScriptFiles();
        if (!empty($scriptFiles)) {
            foreach ($scriptFiles as $scriptFile) {
                $scriptExecutor->runScript($scriptFile);
            }
        }
    }

    protected function isInstalled(): bool
    {
        return $this->applicationState->isInstalled();
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

    protected function updateLocalization(CommandExecutor $commandExecutor): void
    {
        $formattingCode = $this->getContainer()->getParameter(OroLocaleExtension::PARAMETER_FORMATTING_CODE);
        $language = $this->getContainer()->getParameter(OroLocaleExtension::PARAMETER_LANGUAGE);

        $commandExecutor->runCommand(
            UpdateLocalizationCommand::getDefaultName(),
            array_merge(
                ['--process-isolation' => true],
                $this->getLocalizationParametersFromOptions($formattingCode, $language)
            )
        );
    }

    private function validateApplicationUrl(?string $applicationUrl): void
    {
        if (!$applicationUrl) {
            return;
        }

        $violations = $this->getContainer()
            ->get('validator')
            ->validate($applicationUrl, new Url());

        if (!$violations->count()) {
            return;
        }

        throw new \InvalidArgumentException(
            'The value of the "application-url" parameter is invalid. ' . $violations->get(0)->getMessage()
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function presetTestEnvironmentOptions(InputInterface $input, OutputInterface $output): void
    {
        $testEnvDefaultOptionValuesMap = [
            'user-name'         => 'admin',
            'user-email'        => 'admin@example.com',
            'user-firstname'    => 'John',
            'user-lastname'     => 'Doe',
            'user-password'     => 'admin',
            'sample-data'       => 'n',
            'organization-name' => 'OroInc',
            'application-url'   => 'http://localhost/',
            'skip-translations' => true,
            'timeout'           => '600',
            'language'          => 'en',
            'formatting-code'   => 'en_US'
        ];

        foreach ($testEnvDefaultOptionValuesMap as $optionName => $optionValue) {
            if ($input->hasParameterOption('--' . $optionName)) {
                continue;
            }

            $input->setOption($optionName, $optionValue);
        }

        $input->setInteractive(false);
    }

    protected function isTestEnvironment(): bool
    {
        return $this->getContainer()->getParameter('kernel.environment') === 'test';
    }
}
