<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Oro\Bundle\WorkflowBundle\Cache\EventTriggerCache;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Load process configuration from configuration files to the database
 */
class LoadProcessConfigurationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:process:configuration:load';

    /** @var ProcessConfigurationProvider */
    private $configurationProvider;

    /** @var ProcessConfigurator */
    private $processConfigurator;

    /** @var EventTriggerCache */
    private $eventTriggerCache;

    /**
     * @param ProcessConfigurationProvider $configurationProvider
     * @param ProcessConfigurator $processConfigurator
     * @param EventTriggerCache $eventTriggerCache
     */
    public function __construct(
        ProcessConfigurationProvider $configurationProvider,
        ProcessConfigurator $processConfigurator,
        EventTriggerCache $eventTriggerCache
    ) {
        parent::__construct();

        $this->configurationProvider = $configurationProvider;
        $this->processConfigurator = $processConfigurator;
        $this->eventTriggerCache = $eventTriggerCache;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
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
        $processConfiguration = $this->configurationProvider->getProcessConfiguration(
            $input->getOption('directories') ?: null,
            $input->getOption('definitions') ?: null
        );

        $this->processConfigurator->setLogger($this->createConsoleLogger($output));
        $this->processConfigurator->configureProcesses($processConfiguration);
        
        // update triggers cache
        $this->eventTriggerCache->build();
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
