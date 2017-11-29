<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Suite\SuiteConfigurationRegistry;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SuiteSetDividerController implements Controller
{
    /**
     * @var SuiteConfigurationRegistry
     */
    protected $suiteConfigRegistry;

    /**
     * @param SuiteConfigurationRegistry $suiteConfigRegistry
     */
    public function __construct(SuiteConfigurationRegistry $suiteConfigRegistry)
    {
        $this->suiteConfigRegistry = $suiteConfigRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption(
                '--suite-set-divider',
                '-ssd',
                InputOption::VALUE_REQUIRED,
                'Group suites in sets by number of suites per set'
            )
            ->addOption(
                '--max_suite_set_execution_time',
                null,
                InputOption::VALUE_REQUIRED,
                'Group suites in sets based on feature duration statistics.'.PHP_EOL.
                'Maximum time in seconds of execution one suite set'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($divider = (int) $input->getOption('suite-set-divider')) {
            $this->suiteConfigRegistry->generateSetsDividedByCount($divider);
            return;
        }

        if ($maxExecutionTime = (int) $input->getOption('max_suite_set_execution_time')) {
            $this->suiteConfigRegistry->generateSetsByMaxExecutionTime($maxExecutionTime);
            return;
        }
    }
}
