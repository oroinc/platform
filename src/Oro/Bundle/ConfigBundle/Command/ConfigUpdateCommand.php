<?php

namespace Oro\Bundle\ConfigBundle\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update config parameter in global scope
 */
class ConfigUpdateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:config:update';

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Update config parameter in global scope')
            ->addArgument('name', InputArgument::REQUIRED, 'Config parameter name')
            ->addArgument('value', InputArgument::REQUIRED, 'Config parameter value');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configManager = $this->configManager;
        $configManager->set($input->getArgument('name'), $input->getArgument('value'));
        $configManager->flush();
    }
}
