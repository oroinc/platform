<?php

namespace Oro\Bundle\ConfigBundle\Command;

use Oro\Bundle\ConfigBundle\Config\AbstractScopeManager;
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
            ->setDescription('Update config parameter. By default - in global scope')
            ->addArgument('name', InputArgument::REQUIRED, 'Config parameter name')
            ->addArgument('value', InputArgument::REQUIRED, 'Config parameter value')
            ->addOption('scope', null, InputOption::VALUE_REQUIRED, 'Config scope name.', 'global');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configManager = $this->getConfigManager($input->getOption('scope'));
        $configManager->set($input->getArgument('name'), $input->getArgument('value'));
        $configManager->flush();
    }

    /**
     * @param $scopeName
     *
     * @return AbstractScopeManager
     */
    protected function getConfigManager($scopeName)
    {
        return $this->getContainer()->get('oro_config.scope.' . $scopeName);
    }
}
