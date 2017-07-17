<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\SuiteConfigurationRegistry as OroSuiteRegistry;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SuiteDividerController implements Controller
{
    /**
     * @var OroSuiteRegistry
     */
    protected $oroSuiteRegistry;

    /**
     * @param OroSuiteRegistry $oroSuiteRegistry
     */
    public function __construct(OroSuiteRegistry $oroSuiteRegistry)
    {
        $this->oroSuiteRegistry = $oroSuiteRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption(
                '--suite-divider',
                '-sd',
                InputOption::VALUE_REQUIRED,
                'Divide suite to several.'.PHP_EOL.
                'e.g. if AcmeDemo suite has 13 features, and suites-divider is 5, so 3 suites will be created'.PHP_EOL.
                'AcmeDemo#1 and AcmeDemo#2 suites with 5 features, and AcmeDemo#3 with 3 features'.PHP_EOL.
                'Original AcmeDemo suite will be excluded from list.'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$divider = $input->getOption('suite-divider')) {
            return;
        }

        $this->oroSuiteRegistry->divideSuites($divider);
    }
}
