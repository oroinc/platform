<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\InstallerBundle\CommandExecutor;

class PlatformUpdateCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:platform:update')
            ->setDescription(
                'Execute platform application update commands and init platform assets.'
                . ' Please make sure that application cache is up-to-date before run this command.'
                . ' Use cache:clear if needed.'
            )
            ->addOption('timeout', null, InputOption::VALUE_OPTIONAL, 'Timeout for child command execution', 300);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandExecutor = new CommandExecutor(
            $input->hasOption('env') ? $input->getOption('env') : null,
            $output,
            $this->getApplication(),
            $this->getContainer()->get('oro_cache.oro_data_cache_manager')
        );
        $commandExecutor->setDefaultTimeout($input->getOption('timeout'));

        $commandExecutor
            ->runCommand('oro:migration:load', array('--process-isolation' => true))
            ->runCommand('oro:entity-config:clear')
            ->runCommand('oro:entity-extend:clear')
            ->runCommand('oro:workflow:definitions:load')
            ->runCommand('oro:migration:data:load', array('--process-isolation' => true))
            ->runCommand('oro:navigation:init', array('--process-isolation' => true))
            ->runCommand('oro:assets:install', array('--exclude' => array('OroInstallerBundle')))
            ->runCommand('assetic:dump')
            ->runCommand('fos:js-routing:dump', array('--target' => 'web/js/routes.js'))
            ->runCommand('oro:localization:dump')
            ->runCommand('oro:translation:dump')
            ->runCommand('oro:requirejs:build', array('--ignore-errors' => true));
    }
}
