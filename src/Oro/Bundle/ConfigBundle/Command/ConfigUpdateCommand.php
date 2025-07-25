<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates a configuration value in the global scope.
 */
#[AsCommand(
    name: 'oro:config:update',
    description: 'Updates a configuration value in the global scope.'
)]
class ConfigUpdateCommand extends Command
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Config parameter name')
            ->addArgument('value', InputArgument::REQUIRED, 'Config parameter value')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command updates a configuration value in the global scope.

  <info>php %command.full_name% <name> <value></info>

For example, to update the back-office and storefront URLs of an OroCommerce instance respectively:

  <info>php %command.full_name% oro_ui.application_url 'http://admin.example.com'</info>
  <info>php %command.full_name% oro_website.url 'http://store.example.com'</info>
  <info>php %command.full_name% oro_website.secure_url 'https://store.example.com'</info>

HELP
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configManager = $this->configManager;
        $configManager->set($input->getArgument('name'), $input->getArgument('value'));
        $configManager->flush();

        return Command::SUCCESS;
    }
}
