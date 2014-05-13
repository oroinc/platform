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
            ->addOption('process-timeout', null, InputOption::VALUE_OPTIONAL, 'Process timeout', 360)
        ;
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
        $timeout = $input->getOption('process-timeout');
        $commandExecutor
            ->runCommand('oro:migration:load', ['--process-isolation' => true, '--process-timeout' => $timeout])
            ->runCommand('oro:entity-config:clear')
            ->runCommand('oro:entity-extend:clear')
            ->runCommand('oro:workflow:definitions:load', ['--process-timeout' => $timeout])
            ->runCommand('oro:migration:data:load', ['--process-isolation' => true, '--process-timeout' => $timeout])
            ->runCommand('oro:navigation:init', ['--process-isolation' => true, '--process-timeout' => $timeout])
            ->runCommand('oro:assets:install', ['--exclude' => ['OroInstallerBundle'], '--process-timeout' => $timeout])
            ->runCommand('assetic:dump')
            ->runCommand('fos:js-routing:dump', ['--target' => 'web/js/routes.js', '--process-timeout' => $timeout])
            ->runCommand('oro:localization:dump')
            ->runCommand('oro:translation:dump')
            ->runCommand('oro:requirejs:build', ['--ignore-errors' => true, '--process-timeout' => $timeout]);
    }
}
