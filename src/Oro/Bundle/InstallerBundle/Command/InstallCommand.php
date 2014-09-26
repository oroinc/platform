<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\InstallerBundle\Command\Provider\InputOptionProvider;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\InstallerBundle\ScriptExecutor;
use Oro\Bundle\InstallerBundle\ScriptManager;

class InstallCommand extends ContainerAwareCommand implements InstallCommandInterface
{
    /** @var InputOptionProvider */
    protected $inputOptionProvider;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:install')
            ->setDescription('Oro Application Installer.')
            ->addOption('application-url', null, InputOption::VALUE_OPTIONAL, 'Application URL')
            ->addOption('organization-name', null, InputOption::VALUE_OPTIONAL, 'Organization name')
            ->addOption('user-name', null, InputOption::VALUE_OPTIONAL, 'User name')
            ->addOption('user-email', null, InputOption::VALUE_OPTIONAL, 'User email')
            ->addOption('user-firstname', null, InputOption::VALUE_OPTIONAL, 'User first name')
            ->addOption('user-lastname', null, InputOption::VALUE_OPTIONAL, 'User last name')
            ->addOption('user-password', null, InputOption::VALUE_OPTIONAL, 'User password')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force installation')
            ->addOption(
                'timeout',
                null,
                InputOption::VALUE_OPTIONAL,
                'Timeout for child command execution',
                CommandExecutor::DEFAULT_TIMEOUT
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
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->inputOptionProvider = new InputOptionProvider($output, $input, $this->getHelperSet()->get('dialog'));
        if (false === $input->isInteractive()) {
            $this->validate($input);
        }

        $forceInstall = $input->getOption('force');

        $commandExecutor = new CommandExecutor(
            $input->hasOption('env') ? $input->getOption('env') : null,
            $output,
            $this->getApplication(),
            $this->getContainer()->get('oro_cache.oro_data_cache_manager')
        );
        $commandExecutor->setDefaultTimeout($input->getOption('timeout'));

        // if there is application is not installed or no --force option
        $isInstalled = $this->getContainer()->hasParameter('installed')
            && $this->getContainer()->getParameter('installed');

        if ($isInstalled && !$forceInstall) {
            $output->writeln('<comment>ATTENTION</comment>: Oro Application already installed.');
            $output->writeln(
                'To proceed with install - run command with <info>--force</info> option:'
            );
            $output->writeln(sprintf('    <info>%s --force</info>', $this->getName()));
            $output->writeln(
                'To reinstall over existing database - run command with <info>--force --drop-database</info> options:'
            );
            $output->writeln(sprintf('    <info>%s --force --drop-database</info>', $this->getName()));
            $output->writeln(
                '<comment>ATTENTION</comment>: All data will be lost. ' .
                'Database backup is highly recommended before executing this command.'
            );
            $output->writeln('');

            return;
        }

        if ($forceInstall) {
            // if --force option we have to clear cache and set installed to false
            $this->updateInstalledFlag(false);
            $commandExecutor->runCommand(
                'cache:clear',
                array(
                    '--no-optional-warmers' => true,
                    '--process-isolation'   => true
                )
            );
        }

        $output->writeln('<info>Installing Oro Application.</info>');
        $output->writeln('');

        $this
            ->checkStep($output)
            ->prepareStep($commandExecutor, $input->getOption('drop-database'))
            ->loadDataStep($commandExecutor, $output)
            ->finalStep($commandExecutor, $output, $input);

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
                'please run <comment>cache:clear</comment> command with <comment>--env prod</comment> parameter</info>'
            );
        }
    }

    /**
     * @param InputInterface $input
     *
     * @throws \InvalidArgumentException
     */
    public function validate(InputInterface $input)
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

    /**
     * @param OutputInterface $output
     *
     * @return InstallCommand
     * @throws \RuntimeException
     */
    protected function checkStep(OutputInterface $output)
    {
        $output->writeln('<info>Oro requirements check:</info>');

        if (!class_exists('OroRequirements')) {
            require_once $this->getContainer()->getParameter('kernel.root_dir')
                . DIRECTORY_SEPARATOR
                . 'OroRequirements.php';
        }

        $collection = new \OroRequirements();

        $this->renderTable($collection->getMandatoryRequirements(), 'Mandatory requirements', $output);
        $this->renderTable($collection->getPhpIniRequirements(), 'PHP settings', $output);
        $this->renderTable($collection->getOroRequirements(), 'Oro specific requirements', $output);
        $this->renderTable($collection->getRecommendations(), 'Optional recommendations', $output);

        if (count($collection->getFailedRequirements())) {
            throw new \RuntimeException(
                'Some system requirements are not fulfilled. Please check output messages and fix them.'
            );
        }

        $output->writeln('');

        return $this;
    }

    /**
     * Drop schema, clear entity config and extend caches
     *
     * @param CommandExecutor $commandExecutor
     * @param bool            $dropFullDatabase
     *
     * @return InstallCommand
     */
    protected function prepareStep(CommandExecutor $commandExecutor, $dropFullDatabase = false)
    {
        $schemaDropOptions = [
            '--force'             => true,
            '--process-isolation' => true,
        ];

        if ($dropFullDatabase) {
            $schemaDropOptions['--full-database'] = true;
        }

        $commandExecutor
            ->runCommand(
                'doctrine:schema:drop',
                $schemaDropOptions
            )
            ->runCommand('oro:entity-config:cache:clear', ['--no-warmup' => true])
            ->runCommand('oro:entity-extend:cache:clear', ['--no-warmup' => true]);

        return $this;
    }

    /**
     * Update the administrator user
     *
     * @param CommandExecutor $commandExecutor
     */
    protected function updateUser(CommandExecutor $commandExecutor)
    {
        $emailValidator     = function ($value) {
            if (strlen(trim($value)) === 0) {
                throw new \Exception('The email must be specified');
            }

            return $value;
        };
        $firstNameValidator = function ($value) {
            if (strlen(trim($value)) === 0) {
                throw new \Exception('The first name must be specified');
            }

            return $value;
        };
        $lastNameValidator  = function ($value) {
            if (strlen(trim($value)) === 0) {
                throw new \Exception('The last name must be specified');
            }

            return $value;
        };
        $passwordValidator  = function ($value) {
            if (strlen(trim($value)) < 2) {
                throw new \Exception('The password must be at least 2 characters long');
            }

            return $value;
        };

        $options = [
            'user-name'      => [
                'label'                  => 'Username',
                'askMethod'              => 'ask',
                'additionalAskArguments' => [],
                'defaultValue'           => LoadAdminUserData::DEFAULT_ADMIN_USERNAME,
            ],
            'user-email'     => [
                'label'                  => 'Email',
                'askMethod'              => 'askAndValidate',
                'additionalAskArguments' => [$emailValidator],
                'defaultValue'           => null,
            ],
            'user-firstname' => [
                'label'                  => 'First name',
                'askMethod'              => 'askAndValidate',
                'additionalAskArguments' => [$firstNameValidator],
                'defaultValue'           => null,
            ],
            'user-lastname'  => [
                'label'                  => 'Last name',
                'askMethod'              => 'askAndValidate',
                'additionalAskArguments' => [$lastNameValidator],
                'defaultValue'           => null,
            ],
            'user-password'  => [
                'label'                  => 'Password',
                'askMethod'              => 'askHiddenResponseAndValidate',
                'additionalAskArguments' => [$passwordValidator],
                'defaultValue'           => null,
            ],
        ];

        $commandParameters = [];
        foreach ($options as $optionName => $optionData) {
            $commandParameters['--' . $optionName] = $this->inputOptionProvider->get(
                $optionName,
                $optionData['label'],
                $optionData['defaultValue'],
                $optionData['askMethod'],
                $optionData['additionalAskArguments']
            );
        }

        $commandExecutor->runCommand(
            'oro:user:update',
            array_merge(
                [
                    'user-name'           => LoadAdminUserData::DEFAULT_ADMIN_USERNAME,
                    '--process-isolation' => true
                ],
                $commandParameters
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
                'askMethod'              => 'askAndValidate',
                'additionalAskArguments' => [$organizationNameValidator],
                'defaultValue'           => $defaultOrganizationName,
            ]
        ];

        $commandParameters = [];
        foreach ($options as $optionName => $optionData) {
            $commandParameters['--' . $optionName] = $this->inputOptionProvider->get(
                $optionName,
                $optionData['label'],
                $optionData['defaultValue'],
                $optionData['askMethod'],
                $optionData['additionalAskArguments']
            );
        }

        $commandExecutor->runCommand(
            'oro:organization:update',
            array_merge(
                [
                    'organization-name' => 'default',
                    '--process-isolation' => true,
                ],
                $commandParameters
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
                'askMethod'              => 'ask',
                'additionalAskArguments' => [],
            ]
        ];

        foreach ($options as $optionName => $optionData) {
            $configKey    = $optionData['config_key'];
            $defaultValue = $configManager->get($configKey);

            $value = $this->inputOptionProvider->get(
                $optionName,
                $optionData['label'],
                $defaultValue,
                $optionData['askMethod'],
                $optionData['additionalAskArguments']
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
                    '--timeout'           => $commandExecutor->getDefaultTimeout()
                ]
            )
            ->runCommand(
                'oro:workflow:definitions:load',
                [
                    '--process-isolation' => true,
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

        $isDemo = $this->inputOptionProvider->get(
            'sample-data',
            'Load sample data (y/n)',
            false,
            'askConfirmation',
            [false]
        );
        if ($isDemo) {
            // load demo fixtures
            $commandExecutor->runCommand(
                'oro:migration:data:load',
                array(
                    '--process-isolation' => true,
                    '--fixtures-type'     => 'demo'
                )
            );
        }

        $output->writeln('');

        return $this;
    }

    /**
     * @param CommandExecutor $commandExecutor
     * @param OutputInterface $output
     * @param InputInterface $input
     * @return InstallCommand
     */
    protected function finalStep(CommandExecutor $commandExecutor, OutputInterface $output, InputInterface $input)
    {
        $output->writeln('<info>Preparing application.</info>');

        $assetsOptions = array(
            '--exclude' => array('OroInstallerBundle')
        );
        if ($input->hasOption('symlink') && $input->getOption('symlink')) {
            $assetsOptions['--symlink'] = true;
        }

        $commandExecutor
            ->runCommand(
                'oro:navigation:init',
                array(
                    '--process-isolation' => true,
                )
            )
            ->runCommand(
                'fos:js-routing:dump',
                array(
                    '--target'            => 'web/js/routes.js',
                    '--process-isolation' => true,
                )
            )
            ->runCommand('oro:localization:dump')
            ->runCommand(
                'oro:assets:install',
                $assetsOptions
            )
            ->runCommand(
                'assetic:dump',
                array(
                    '--process-isolation' => true,
                )
            )
            ->runCommand(
                'oro:translation:dump',
                array(
                    '--process-isolation' => true,
                )
            )
            ->runCommand(
                'oro:requirejs:build',
                array(
                    '--ignore-errors'     => true,
                    '--process-isolation' => true,
                )
            );

        // run installer scripts
        $this->processInstallerScripts($output, $commandExecutor);

        $this->updateInstalledFlag(date('c'));

        // clear the cache set installed flag in DI container
        $commandExecutor->runCommand(
            'cache:clear',
            array(
                '--process-isolation' => true,
            )
        );

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
     * Render requirements table
     *
     * @param array           $collection
     * @param string          $header
     * @param OutputInterface $output
     */
    protected function renderTable(array $collection, $header, OutputInterface $output)
    {
        /** @var TableHelper $table */
        $table = $this->getHelperSet()->get('table');

        $table
            ->setHeaders(array('Check  ', $header))
            ->setRows(array());

        /** @var \Requirement $requirement */
        foreach ($collection as $requirement) {
            if ($requirement->isFulfilled()) {
                $table->addRow(array('OK', $requirement->getTestMessage()));
            } else {
                $table->addRow(
                    array(
                        $requirement->isOptional() ? 'WARNING' : 'ERROR',
                        $requirement->getHelpText()
                    )
                );
            }
        }

        $table->render($output);
    }
}
