<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\SuiteConfigurationRegistry;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SuiteSetDividerController implements Controller
{
    const GENERATED_SUITE_SET_PREFIX = 'AutoSuiteSet#';

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
                InputOption::VALUE_REQUIRED
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$divider = (int) $input->getOption('suite-set-divider')) {
            return;
        }

        $this->suiteConfigRegistry->genererateSets($divider);
    }
}
