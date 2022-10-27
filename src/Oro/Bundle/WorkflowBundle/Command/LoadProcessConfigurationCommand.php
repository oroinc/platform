<?php
declare(strict_types=1);

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
 * Loads process definitions to the database.
 */
class LoadProcessConfigurationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:process:configuration:load';

    private ProcessConfigurationProvider $configurationProvider;
    private ProcessConfigurator $processConfigurator;
    private EventTriggerCache $eventTriggerCache;

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

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption(
                'directories',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Directories with process configurations'
            )
            ->addOption(
                'definitions',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Process names'
            )
            ->setDescription('Loads process definitions to the database.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command loads process definitions
from configuration files to the database.

  <info>php %command.full_name%</info>

The <info>--directories</info> option can be used to specify custom location(s)
of the process configuration files:

  <info>php %command.full_name% --directories=<path1> --directories=<path2></info>

The <info>--definitions</info> option can be used to load only the specified processes:

  <info>php %command.full_name% --definitions=<definition1> --definitions=<definition2></info>

HELP
            )
            ->addUsage('--directories=<path1> --directories=<path2>')
            ->addUsage('--definitions=<definition1> --definitions=<definition2>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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

        return 0;
    }

    protected function createConsoleLogger(OutputInterface $output): ConsoleLogger
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
