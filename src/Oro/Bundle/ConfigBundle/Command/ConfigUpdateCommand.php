<?php

namespace Oro\Bundle\ConfigBundle\Command;

use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigUpdateCommand extends ContainerAwareCommand
{
    /**
     * @var GlobalScopeManager
     */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:config:update')
            ->setDescription('Application config update.')
            ->addOption('application-url', null, InputOption::VALUE_REQUIRED, 'Application URL');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOptions();
        if (!empty($options['application-url'])) {
            $this->getConfigManager()->set('oro_ui.application_url', $options['application-url']);
        }
        $this->getConfigManager()->flush();
    }

    /**
     * @return GlobalScopeManager
     */
    protected function getConfigManager()
    {
        if (!$this->configManager) {
            $this->configManager = $this->getContainer()->get('oro_config.scope.global');
        }

        return $this->configManager;
    }
}
