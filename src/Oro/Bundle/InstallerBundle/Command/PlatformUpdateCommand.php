<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandExecutor = new CommandExecutor(
            $input->hasOption('env') ? $input->getOption('env') : null,
            $output,
            $this->getApplication()
        );
        $commandExecutor
            ->runCommand(
                'oro:migration:load',
                array(
                    '--process-isolation' => true,
                    '--process-timeout' => 300
                )
            )
            ->runCommand('oro:navigation:init', array('--process-isolation' => true))
            ->runCommand('assets:install')
            ->runCommand('assetic:dump')
            ->runCommand('fos:js-routing:dump', array('--target' => 'web/js/routes.js'))
            ->runCommand('oro:localization:dump')
            ->runCommand('oro:translation:dump')
            ->runCommand('oro:requirejs:build', array('--ignore-errors' => true));
    }
}
