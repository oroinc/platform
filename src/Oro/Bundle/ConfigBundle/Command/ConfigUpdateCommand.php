<?php

namespace Oro\Bundle\ConfigBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigUpdateCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:config:update')
            ->setDescription('Update config parameter in global scope')
            ->addArgument('name', InputArgument::REQUIRED, 'Config parameter name')
            ->addArgument('value', InputArgument::REQUIRED, 'Config parameter value');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configManager = $this->getContainer()->get('oro_config.scope.global');
        $configManager->set($input->getArgument('name'), $input->getArgument('value'));
        $configManager->flush();
    }
}
