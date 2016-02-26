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
            ->setDescription('Global application config update.')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Config name')
            ->addOption('value', null, InputOption::VALUE_REQUIRED, 'Config value');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOptions();
        $this->checkRequiredOptions($options);

        if (!empty($options['name'])) {
            $this->getConfigManager()->set($options['name'], $options['value']);
        }
        $this->getConfigManager()->flush();
    }

    /**
     * @param array $options
     * @throws \InvalidArgumentException
     * @return $this
     */
    protected function checkRequiredOptions($options)
    {
        $requiredOptions = [
            'name',
            'value'
        ];

        foreach ($requiredOptions as $requiredOption) {
            if (empty($options[$requiredOption])) {
                throw new \InvalidArgumentException('--' . $requiredOption . ' option required');
            }
        }

        return $this;
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
