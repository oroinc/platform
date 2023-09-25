<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Views a configuration value in the global scope.
 */
class ConfigViewCommand extends Command
{
    protected static $defaultName = 'oro:config:view';

    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Config parameter name')
            ->setDescription('Views a configuration value in the global scope.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command views a configuration value in the global scope.

  <info>php %command.full_name% <name></info>

For example, to view the back-office and storefront URLs of an OroCommerce instance respectively:

  <info>php %command.full_name% oro_ui.application_url</info>
  <info>php %command.full_name% oro_website.url</info>
  <info>php %command.full_name% oro_website.secure_url</info>

HELP
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configManager = $this->configManager;
        $value = $configManager->get($input->getArgument('name'));
        $output->writeln($value);

        return Command::SUCCESS;
    }
}
