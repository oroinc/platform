<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Behat\Testwork\Suite\SuiteRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\SuiteConfigurationRegistry as OroSuiteRegistry;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SuiteController implements Controller
{
    /**
     * @var OroSuiteRegistry
     */
    protected $suiteConfigRegistry;

    /**
     * @var SuiteRegistry
     */
    protected $behatSuiteRegistry;

    /**
     * @param OroSuiteRegistry $suiteConfigRegistry
     */
    public function __construct(OroSuiteRegistry $suiteConfigRegistry, SuiteRegistry $behatSuiteRegistry)
    {
        $this->suiteConfigRegistry = $suiteConfigRegistry;
        $this->behatSuiteRegistry = $behatSuiteRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption(
                '--suite',
                '-s',
                InputOption::VALUE_REQUIRED,
                'Only execute a specific suite.'
            )
            ->addOption(
                '--suite-set',
                '-ss',
                InputOption::VALUE_REQUIRED,
                'Only execute a specific set of suites'
            )
        ;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($suiteName = $input->getOption('suite')) {
            $this->registerSuite($suiteName);
        }

        if ($suiteSet = $input->getOption('suite-set')) {
            $this->registerSuiteSet($suiteSet);
        }

        if (!$suiteName && !$suiteSet) {
            $this->registerAll();
        }
    }

    private function registerSuite($suiteName)
    {
        $suiteConfig = $this->suiteConfigRegistry->getSuiteConfig($suiteName);
        $this->behatSuiteRegistry->registerSuiteConfiguration(
            $suiteConfig->getName(),
            $suiteConfig->getType(),
            $suiteConfig->getSettings()
        );
    }

    private function registerSuiteSet($suiteSet)
    {
        foreach ($this->suiteConfigRegistry->getSet($suiteSet) as $suiteConfig) {
            $this->behatSuiteRegistry->registerSuiteConfiguration(
                $suiteConfig->getName(),
                $suiteConfig->getType(),
                $suiteConfig->getSettings()
            );
        }
    }

    private function registerAll()
    {
        foreach ($this->suiteConfigRegistry->getSuiteConfigurations() as $suiteConfig) {
            $this->behatSuiteRegistry->registerSuiteConfiguration(
                $suiteConfig->getName(),
                $suiteConfig->getType(),
                $suiteConfig->getSettings()
            );
        }
    }
}
