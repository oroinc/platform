<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\InstallerBundle\InstallerProvider;

class InstallCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oro:install')
            ->setDescription('Oro Application Installer.')
            ->addOption('user-name', null, InputOption::VALUE_OPTIONAL, 'User name')
            ->addOption('user-email', null, InputOption::VALUE_OPTIONAL, 'User email')
            ->addOption('user-firstname', null, InputOption::VALUE_OPTIONAL, 'User first name')
            ->addOption('user-lastname', null, InputOption::VALUE_OPTIONAL, 'User last name')
            ->addOption('user-password', null, InputOption::VALUE_OPTIONAL, 'User password')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force installation')
            ->addOption(
                'sample-data',
                null,
                InputOption::VALUE_OPTIONAL,
                'Determines whether sample data need to be loaded or not'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $forceInstall = $input->getOption('force');

        $commandExecutor = new CommandExecutor($input, $output, $this->getApplication());

        // if there is application is not installed or no --force option
        if ($this->getContainer()->hasParameter('installed') && $this->getContainer()->getParameter('installed')
            && !$forceInstall
        ) {
            throw new \RuntimeException('Oro Application already installed.');
        } elseif ($forceInstall) {
            // if --force option we have to clear cache
            $commandExecutor->runCommand('cache:clear');
        }

        $output->writeln('<info>Installing Oro Application.</info>');
        $output->writeln('');

        $this
            ->checkStep($output)
            ->setupStep($commandExecutor, $input, $output)
            ->finalStep($commandExecutor, $input, $output);

        $output->writeln('');
        $output->writeln('<info>Oro Application has been successfully installed.</info>');
    }

    /**
     * @param OutputInterface $output
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
     * @param CommandExecutor $commandExecutor
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return InstallCommand
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setupStep(CommandExecutor $commandExecutor, InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Setting up database.</info>');

        $dialog    = $this->getHelperSet()->get('dialog');
        $container = $this->getContainer();
        $options   = $input->getOptions();

        $input->setInteractive(false);

        $commandExecutor
            ->runCommand('oro:entity-extend:clear')
            ->runCommand(
                'doctrine:schema:drop',
                array('--force' => true, '--full-database' => true)
            )
            ->runCommand('doctrine:schema:create')
            ->runCommand('oro:entity-config:init')
            ->runCommand('oro:entity-extend:init')
            ->runCommand(
                'oro:entity-extend:update-config',
                array('--process-isolation' => true)
            )
            ->runCommand(
                'doctrine:schema:update',
                array('--process-isolation' => true, '--force' => true, '--no-interaction' => true)
            )
            ->runCommand(
                'doctrine:fixtures:load',
                array('--process-isolation' => true, '--no-interaction' => true, '--append' => true)
            );

        $output->writeln('');
        $output->writeln('<info>Administration setup.</info>');

        $user = $container->get('oro_user.manager')->createUser();
        $role = $container
            ->get('doctrine.orm.entity_manager')
            ->getRepository('OroUserBundle:Role')
            ->findOneBy(array('role' => 'ROLE_ADMINISTRATOR'));

        $businessUnit = $container
            ->get('doctrine.orm.entity_manager')
            ->getRepository('OroOrganizationBundle:BusinessUnit')
            ->findOneBy(array('name' => 'Main'));

        $passValidator = function ($value) {
            if (strlen(trim($value)) < 2) {
                throw new \Exception('The password must be at least 2 characters long');
            }

            return $value;
        };

        $userName      = isset($options['user-name'])
            ? $options['user-name']
            : $dialog->ask($output, '<question>Username:</question> ');
        $userEmail     = isset($options['user-email'])
            ? $options['user-email']
            : $dialog->ask($output, '<question>Email:</question> ');
        $userFirstName = isset($options['user-firstname'])
            ? $options['user-firstname']
            : $dialog->ask($output, '<question>First name:</question> ');
        $userLastName  = isset($options['user-lastname'])
            ? $options['user-lastname']
            : $dialog->ask($output, '<question>Last name:</question> ');
        $userPassword  = isset($options['user-password'])
            ? $options['user-password']
            : $dialog->askHiddenResponseAndValidate($output, '<question>Password:</question> ', $passValidator);
        $user
            ->setUsername($userName)
            ->setEmail($userEmail)
            ->setFirstName($userFirstName)
            ->setLastName($userLastName)
            ->setPlainPassword($userPassword)
            ->setEnabled(true)
            ->addRole($role)
            ->setOwner($businessUnit)
            ->addBusinessUnit($businessUnit);

        $container->get('oro_user.manager')->updateUser($user);

        $demo = isset($options['sample-data'])
            ? strtolower($options['sample-data']) == 'y'
            : $dialog->askConfirmation($output, '<question>Load sample data (y/n)?</question> ', false);

        // load demo fixtures
        if ($demo) {
            $commandExecutor->runCommand(
                'oro:demo:fixtures:load',
                array('--process-isolation' => true, '--process-timeout' => 300)
            );
        }

        $output->writeln('');

        return $this;
    }

    /**
     * @param CommandExecutor $commandExecutor
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return InstallCommand
     */
    protected function finalStep(CommandExecutor $commandExecutor, InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Preparing application.</info>');

        $input->setInteractive(false);

        $commandExecutor
            ->runCommand('oro:search:create-index')
            ->runCommand('oro:navigation:init')
            ->runCommand('fos:js-routing:dump', array('--target' => 'web/js/routes.js'))
            ->runCommand('oro:localization:dump')
            ->runCommand('assets:install')
            ->runCommand('assetic:dump')
            ->runCommand('oro:translation:dump')
            ->runCommand('oro:requirejs:build');

        // run installer scripts
        $this->processInstallerScripts($output);

        // update installed flag in parameters.yml
        $dumper                        = $this->getContainer()->get('oro_installer.yaml_persister');
        $params                        = $dumper->parse();
        $params['system']['installed'] = date('c');
        $dumper->dump($params);

        // clear the cache set installed flag in DI container
        $commandExecutor->runCommand('cache:clear');

        $output->writeln('');

        return $this;
    }

    /**
     * Process installer scripts
     *
     * @param OutputInterface $output
     */
    protected function processInstallerScripts(OutputInterface $output)
    {
        /** @var InstallerProvider $installerProvider */
        $installerProvider = $this->getContainer()->get('oro_installer.installer_provider');
        $installerScripts  = $installerProvider->getInstallerScriptList();
        if (!empty($installerScripts)) {
            foreach ($installerScripts as $installerScript) {
                $installerProvider->runInstallerScript(
                    $installerScript['key'],
                    $output,
                    $this->getContainer()
                );
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
        $table = $this->getHelperSet()->get('table');

        $table
            ->setHeaders(array('Check  ', $header))
            ->setRows(array());

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
