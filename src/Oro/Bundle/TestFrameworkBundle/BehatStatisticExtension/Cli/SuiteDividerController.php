<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Suite\SuiteConfigurationRegistry;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SuiteDividerController implements Controller
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
                '--suite-divider',
                '-sd',
                InputOption::VALUE_REQUIRED,
                'Divide suite to several.'.PHP_EOL.
                'e.g. if AcmeDemo suite has 13 features, and suites-divider is 5, so 3 suites will be created'.PHP_EOL.
                'AcmeDemo#1 with 5 features, and AcmeDemo#2 and AcmeDemo#3 suites with 4 features'.PHP_EOL.
                'Original AcmeDemo suite will be excluded from list.'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$divider = (int) $input->getOption('suite-divider')) {
            return;
        }

        $this->suiteConfigRegistry->divideSuites($divider);
    }
}
