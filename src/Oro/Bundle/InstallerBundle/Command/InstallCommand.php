<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Composer\Question\StrictConfirmationQuestion;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\InstallerBundle\Command\Provider\InputOptionProvider;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\InstallerBundle\InstallerEvent;
use Oro\Bundle\InstallerBundle\InstallerEvents;
use Oro\Bundle\InstallerBundle\ScriptExecutor;
use Oro\Bundle\InstallerBundle\ScriptManager;
use Oro\Bundle\LocaleBundle\Command\UpdateLocalizationCommand;
use Oro\Bundle\LocaleBundle\DependencyInjection\OroLocaleExtension;
use Oro\Bundle\SecurityBundle\Command\LoadConfigurablePermissionCommand;
use Oro\Bundle\SecurityBundle\Command\LoadPermissionConfigurationCommand;
use Oro\Bundle\TranslationBundle\Command\OroLanguageUpdateCommand;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Intl\Intl;

/**
 * Command installs application with all schema and data migrations, prepares assets and application cache
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class InstallCommand extends AbstractCommand implements InstallCommandInterface
{
    public const NAME = 'oro:install';

    /** @var InputOptionProvider */
    protected $inputOptionProvider;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Oro Application Installer.')
            ->addOption('application-url', null, InputOption::VALUE_OPTIONAL, 'Application URL')
            ->addOption('organization-name', null, InputOption::VALUE_OPTIONAL, 'Organization name')
            ->addOption('user-name', null, InputOption::VALUE_OPTIONAL, 'User name')
            ->addOption('user-email', null, InputOption::VALUE_OPTIONAL, 'User email')
            ->addOption('user-firstname', null, InputOption::VALUE_OPTIONAL, 'User first name')
            ->addOption('user-lastname', null, InputOption::VALUE_OPTIONAL, 'User last name')
            ->addOption('user-password', null, InputOption::VALUE_OPTIONAL, 'User password')
            ->addOption(
                'skip-assets',
                null,
                InputOption::VALUE_NONE,
                'Skip UI related commands during installation'
            )
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlinks the assets instead of copying it')
            ->addOption(
                'sample-data',
                null,
                InputOption::VALUE_OPTIONAL,
                'Determines whether sample data need to be loaded or not'
            )
            ->addOption(
                'drop-database',
                null,
                InputOption::VALUE_NONE,
                'Database will be dropped and all data will be deleted.'
            )
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
            )
            ->addOption(
                UpdateLocalizationCommand::OPTION_LANGUAGE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Localization language'
            )
            ->addOption(
                UpdateLocalizationCommand::OPTION_FORMATTING_CODE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Localization formatting code'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->inputOptionProvider = new InputOptionProvider($output, $input, $this->getHelperSet()->get('question'));

        if (false === $input->isInteractive()) {
            $this->validate($input);
        }

        $skipAssets = $input->getOption('skip-assets');
        $commandExecutor = $this->getCommandExecutor($input, $output);

        if ($this->isInstalled()) {
            $output->writeln('<comment>ATTENTION</comment>: Oro Application already installed.');
            $output->writeln(
                'To proceed with install: '
            );
            $output->writeln(' - set parameter <info>installed: false</info> in config/parameters.yml.');
            $output->writeln(' - remove caches in var/cache folder manually');
            $output->writeln(' - drop database manually or reinstall over existing database.');
            $output->writeln(
                'To reinstall over existing database - run command with <info>--drop-database</info> option:'
            );
            $output->writeln(sprintf('    <info>%s --drop-database</info>', $this->getName()));
            $output->writeln(
                '<comment>ATTENTION</comment>: All data will be lost. ' .
                'Database backup is highly recommended before executing this command.'
            );
            $output->writeln('');

            return 0;
        }

        $output->writeln('<info>Installing Oro Application.</info>');
        $output->writeln('');

        $exitCode = $this->checkRequirements($commandExecutor);
        if ($exitCode > 0) {
            return $exitCode;
        }

        $eventDispatcher = $this->getEventDispatcher();
        $event = new InstallerEvent($this, $input, $output, $commandExecutor);

        try {
            $this->prepareStep($input, $output);

            $eventDispatcher->dispatch(InstallerEvents::INSTALLER_BEFORE_DATABASE_PREPARATION, $event);
            $this->loadDataStep($commandExecutor, $output);
            $eventDispatcher->dispatch(InstallerEvents::INSTALLER_AFTER_DATABASE_PREPARATION, $event);

            $this->finalStep($commandExecutor, $output, $input, $skipAssets);
        } catch (\Exception $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return $commandExecutor->getLastCommandExitCode();
        }

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
            'https://oroinc.com/orocrm/doc/current/install-upgrade/post-install-steps#activate-background-tasks' .
            '</comment> ' .
            'for more information.</info>'
        );

        return 0;
    }

    /**
     * @param InputInterface $input
     *
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

        $this->validateLocalizationOptions($input);
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
            ['--ignore-errors' => true, '--verbose' => 2]
        );

        return $commandExecutor->getLastCommandExitCode();
    }

    /**
     * Drop schema, clear entity config and extend caches
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return InstallCommand
     */
    protected function prepareStep(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('drop-database')) {
            $output->writeln('<info>Drop schema.</info>');
            $managers = $this->getContainer()->get('doctrine')->getManagers();
            foreach ($managers as $name => $manager) {
                if ($manager instanceof EntityManager) {
                    $tool = new SchemaTool($manager);
                    $tool->dropDatabase();
                }
            }
        }

        return $this;
    }

    /**
     * @param string $message
     *
     * @return callable
     */
    protected function getNotBlankValidator($message)
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
     *
     * @param CommandExecutor $commandExecutor
     */
    protected function updateUser(CommandExecutor $commandExecutor)
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
                $this->getCommandParametersFromOptions($options)
            )
        );
    }

    /**
     * Update the organization
     *
     * @param CommandExecutor $commandExecutor
     */
    protected function updateOrganization(CommandExecutor $commandExecutor)
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
                $this->getCommandParametersFromOptions($options)
            )
        );
    }

    /**
     * Update system settings such as app url, company name and short name
     */
    protected function updateSystemSettings()
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->getContainer()->get('oro_config.global');
        $options       = [
            'application-url' => [
                'label'                  => 'Application URL',
                'config_key'             => 'oro_ui.application_url',
            ]
        ];

        foreach ($options as $optionName => $optionData) {
            $configKey    = $optionData['config_key'];
            $defaultValue = $configManager->get($configKey);

            $value = $this->inputOptionProvider->get(
                $optionName,
                $optionData['label'],
                $defaultValue,
                ['constructorArgs' => [$defaultValue]]
            );

            // update setting if it's not empty and not equal to default value
            if (!empty($value) && $value !== $defaultValue) {
                $configManager->set($configKey, $value);
            }
        }

        $configManager->flush();
    }

    /**
     * @param CommandExecutor $commandExecutor
     * @param OutputInterface $output
     *
     * @return InstallCommand
     */
    protected function loadDataStep(CommandExecutor $commandExecutor, OutputInterface $output)
    {
        $output->writeln('<info>Setting up database.</info>');

        $commandExecutor
            ->runCommand(
                'oro:migration:load',
                [
                    '--force'             => true,
                    '--process-isolation' => true,
                    '--timeout'           => $commandExecutor->getDefaultOption('process-timeout'),
                ]
            )
            ->runCommand(
                LoadPermissionConfigurationCommand::NAME,
                [
                    '--process-isolation' => true
                ]
            )
            ->runCommand(
                LoadConfigurablePermissionCommand::NAME,
                [
                    '--process-isolation' => true
                ]
            )
            ->runCommand(
                'oro:cron:definitions:load',
                [
                    '--process-isolation' => true
                ]
            )
            ->runCommand(
                'oro:workflow:definitions:load',
                [
                    '--process-isolation' => true
                ]
            )
            ->runCommand(
                'oro:process:configuration:load',
                [
                    '--process-isolation' => true
                ]
            )
            ->runCommand(
                'oro:migration:data:load',
                [
                    '--process-isolation' => true,
                    '--no-interaction'    => true,
                ]
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
            false,
            [
                'class' => StrictConfirmationQuestion::class,
                'constructorArgs' => [false]
            ]
        );
        if ($isDemo) {
            // load demo fixtures
            $commandExecutor->runCommand(
                'oro:migration:data:load',
                [
                    '--process-isolation'  => true,
                    '--fixtures-type'      => 'demo',
                ]
            );
        }

        $output->writeln('');

        return $this;
    }

    /**
     * @param CommandExecutor $commandExecutor
     * @param OutputInterface $output
     * @param InputInterface $input
     * @param boolean $skipAssets
     * @return InstallCommand
     */
    protected function finalStep(
        CommandExecutor $commandExecutor,
        OutputInterface $output,
        InputInterface $input,
        $skipAssets
    ) {
        $output->writeln('<info>Preparing application.</info>');

        $assetsOptions = [];
        if ($input->hasOption('symlink') && $input->getOption('symlink')) {
            $assetsOptions['--symlink'] = true;
        }

        $this->processTranslations($input, $commandExecutor);

        if (!$skipAssets) {
            $commandExecutor->runCommand(
                'fos:js-routing:dump',
                [
                    '--process-isolation' => true,
                ]
            )
                ->runCommand('oro:localization:dump')
                ->runCommand(
                    'assets:install',
                    $assetsOptions
                )
                ->runCommand('oro:assets:build', ['--npm-install'=> true])
                ->runCommand(
                    'oro:requirejs:build',
                    [
                        '--ignore-errors' => true,
                        '--process-isolation' => true,
                    ]
                );
        }
        // run installer scripts
        $this->processInstallerScripts($output, $commandExecutor);

        $this->updateInstalledFlag(date('c'));

        // clear the cache and set installed flag in DI container
        $cacheClearOptions = ['--process-isolation' => true];
        if ($commandExecutor->getDefaultOption('no-debug')) {
            $cacheClearOptions['--no-debug'] = true;
        }
        if ($input->getOption('env')) {
            $cacheClearOptions['--env'] = $input->getOption('env');
        }
        $commandExecutor->runCommand('cache:clear', $cacheClearOptions);

        if (!$skipAssets) {
            /**
             * Place this launch of command after the launch of 'assetic-dump' in BAP-16333
             */
            $commandExecutor->runCommand(
                'oro:translation:dump',
                [
                    '--process-isolation' => true,
                ]
            );
        }

        $output->writeln('');

        return $this;
    }

    /**
     * Update installed flag in parameters.yml
     *
     * @param bool|string $installed
     */
    protected function updateInstalledFlag($installed)
    {
        $dumper                        = $this->getContainer()->get('oro_installer.yaml_persister');
        $params                        = $dumper->parse();
        $params['system']['installed'] = $installed;
        $dumper->dump($params);
    }

    /**
     * Clears the state of all database checkers to make sure they will recheck the database state
     */
    protected function clearCheckDatabaseState()
    {
        $this->getContainer()->get('oro_entity.database_checker.state_manager')->clearState();
    }

    /**
     * Process installer scripts
     *
     * @param OutputInterface $output
     * @param CommandExecutor $commandExecutor
     */
    protected function processInstallerScripts(OutputInterface $output, CommandExecutor $commandExecutor)
    {
        $scriptExecutor = new ScriptExecutor($output, $this->getContainer(), $commandExecutor);
        /** @var ScriptManager $scriptManager */
        $scriptManager = $this->getContainer()->get('oro_installer.script_manager');
        $scriptFiles   = $scriptManager->getScriptFiles();
        if (!empty($scriptFiles)) {
            foreach ($scriptFiles as $scriptFile) {
                $scriptExecutor->runScript($scriptFile);
            }
        }
    }

    /**
     * @return bool
     */
    protected function isInstalled()
    {
        $isInstalled = $this->getContainer()->hasParameter('installed')
            && $this->getContainer()->getParameter('installed');

        return $isInstalled;
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

    /**
     * @param CommandExecutor $commandExecutor
     */
    protected function updateLocalization(CommandExecutor $commandExecutor)
    {
        $formattingCode = $this->getContainer()->getParameter(OroLocaleExtension::PARAMETER_FORMATTING_CODE);
        $language = $this->getContainer()->getParameter(OroLocaleExtension::PARAMETER_LANGUAGE);

        $options = [
            'formatting-code' => [
                'label' => 'Formatting Code',
                'options' => [
                    'constructorArgs' => [$formattingCode],
                    'settings' => [
                        'validator' => [
                            function ($value) {
                                $this->validateFormattingCode($value);
                                return $value;
                            }
                        ]
                    ]
                ],
                'defaultValue' => $formattingCode
            ],
            'language' => [
                'label' => 'Language',
                'options' => [
                    'constructorArgs' => [$language],
                    'settings' => [
                        'validator' => [
                            function ($value) {
                                $this->validateLanguage($value);
                                return $value;
                            }
                        ]
                    ]
                ],
                'defaultValue' => $language
            ]
        ];

        $commandExecutor->runCommand(
            UpdateLocalizationCommand::NAME,
            array_merge(
                [
                    '--process-isolation' => true
                ],
                $this->getCommandParametersFromOptions($options)
            )
        );
    }

    /**
     * @param InputInterface $input
     */
    private function validateLocalizationOptions(InputInterface $input): void
    {
        $formattingCode = $input->getOption('formatting-code');
        if ($formattingCode) {
            $this->validateFormattingCode($formattingCode);
        }

        $language = (string)$input->getOption('language');
        if ($language) {
            $this->validateLanguage($language);
        }
    }

    /**
     * @param string $locale
     * @throws \InvalidArgumentException
     */
    private function validateFormattingCode(string $locale): void
    {
        $locales = array_keys(Intl::getLocaleBundle()->getLocaleNames());
        if (!in_array($locale, $locales, true)) {
            throw new \InvalidArgumentException($this->getExceptionMessage('formatting', $locale, $locales));
        }
    }

    /**
     * @param string $language
     * @throws \InvalidArgumentException
     */
    private function validateLanguage(string $language)
    {
        $languages = array_keys(Intl::getLanguageBundle()->getLanguageNames());
        if (!in_array($language, $languages, true)) {
            throw new \InvalidArgumentException($this->getExceptionMessage('language', $language, $languages));
        }
    }

    /**
     * @param string $optionName
     * @param string $localeCode
     * @param array $availableLocaleCodes
     * @return string
     */
    private function getExceptionMessage(string $optionName, string $localeCode, array $availableLocaleCodes):string
    {
        $exceptionMessage = sprintf('"%s" is not a valid %s code!', $localeCode, $optionName);
        $alternatives = $this->getAlternatives($localeCode, $availableLocaleCodes);
        if ($alternatives) {
            $exceptionMessage .= sprintf("\nDid you mean %s?\n", $alternatives);
        }

        return $exceptionMessage;
    }

    /**
     * @param array $options
     * @return array
     */
    private function getCommandParametersFromOptions(array $options): array
    {
        $commandParameters = [];
        foreach ($options as $optionName => $optionData) {
            $commandParameters['--' . $optionName] = $this->inputOptionProvider->get(
                $optionName,
                $optionData['label'],
                $optionData['defaultValue'],
                $optionData['options']
            );
        }

        return $commandParameters;
    }

    /**
     * @param string $name
     * @param array $items
     * @return string
     */
    private function getAlternatives(string $name, array $items): string
    {
        $alternatives = [];
        foreach ($items as $item) {
            $lev = levenshtein($name, $item);
            if ($lev <= strlen($name) / 2 || false !== strpos($item, $name)) {
                $alternatives[$item] = $lev;
            }
        }
        asort($alternatives);

        return implode(', ', array_keys($alternatives));
    }
}
