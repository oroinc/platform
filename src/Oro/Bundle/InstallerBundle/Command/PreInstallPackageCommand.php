<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\InstallerBundle\CommandExecutor;

class PreInstallPackageCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oro:package:pre-install')
            ->setDescription('Run pre install package commands.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandExecutor = new CommandExecutor($input, $output, $this->getApplication());
        $commandExecutor->runCommand('oro:entity-config:update')
            ->runCommand('oro:entity-extend:init')
            ->runCommand('oro:entity-extend:update-config')
            ->runCommand('doctrine:schema:update')
            ->runCommand('oro:search:create-index')
            ->runCommand('oro:navigation:init')
            ->runCommand('assets:install')
            ->runCommand('assetic:dump')
            ->runCommand('fos:js-routing:dump', array('--target' => 'js/routes.js'))
            ->runCommand('oro:localization:dump')
            ->runCommand('oro:translation:dump')
            ->runCommand('oro:requirejs:build');
    }
}
