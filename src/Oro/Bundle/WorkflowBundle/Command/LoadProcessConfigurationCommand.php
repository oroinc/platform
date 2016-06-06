<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Psr\Log\LogLevel;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;

class LoadProcessConfigurationCommand extends ContainerAwareCommand
{
    const NAME = 'oro:process:configuration:load';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Load process configuration from configuration files to the database')
            ->addOption(
                'directories',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Directories used to find configuration files'
            )
            ->addOption(
                'definitions',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Names of the process definitions that should be loaded'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $usedDirectories = $input->getOption('directories');
        $usedDirectories = $usedDirectories ?: null;

        $usedDefinitions = $input->getOption('definitions');
        $usedDefinitions = $usedDefinitions ?: null;

        /** @var ProcessConfigurationProvider $configurationProvider */
        $configurationProvider = $this->getContainer()->get('oro_workflow.configuration.provider.process_config');
        $processConfiguration = $configurationProvider->getProcessConfiguration(
            $usedDirectories,
            $usedDefinitions
        );

        $processConfigurator = $this->getContainer()->get('oro_workflow.process.configurator');

        $processConfigurator->setLogger($this->createConsoleLogger($output));

        $processConfigurator->configureProcesses($processConfiguration);
        
        // update triggers cache
        $this->getContainer()->get('oro_workflow.cache.process_trigger')->build();
    }

    /**
     * @param OutputInterface $output
     *
     * @return ConsoleLogger
     */
    protected function createConsoleLogger(OutputInterface $output)
    {
        return new ConsoleLogger($output, [
            LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::ALERT => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::CRITICAL => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::ERROR => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::WARNING => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::DEBUG => OutputInterface::VERBOSITY_NORMAL,
        ]);
    }
}
