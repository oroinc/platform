<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Cli;

use Behat\Testwork\Cli\Controller;
use Behat\Testwork\Suite\SuiteRegistry;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Suite\SuiteConfigurationRegistry;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SuiteController implements Controller
{
    /**
     * @var SuiteConfigurationRegistry
     */
    protected $suiteConfigRegistry;

    /**
     * @var SuiteRegistry
     */
    protected $behatSuiteRegistry;

    /**
     * @param SuiteConfigurationRegistry $suiteConfigRegistry
     * @param SuiteRegistry $behatSuiteRegistry
     */
    public function __construct(SuiteConfigurationRegistry $suiteConfigRegistry, SuiteRegistry $behatSuiteRegistry)
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
            $suiteConfig->hasSetting('type') ? $suiteConfig->getSetting('type') : null,
            $suiteConfig->getSettings()
        );
    }

    private function registerSuiteSet($suiteSet)
    {
        foreach ($this->suiteConfigRegistry->getSet($suiteSet) as $suiteConfig) {
            $this->behatSuiteRegistry->registerSuiteConfiguration(
                $suiteConfig->getName(),
                $suiteConfig->hasSetting('type') ? $suiteConfig->getSetting('type') : null,
                $suiteConfig->getSettings()
            );
        }
    }

    private function registerAll()
    {
        foreach ($this->suiteConfigRegistry->getSuites() as $suiteConfig) {
            $this->behatSuiteRegistry->registerSuiteConfiguration(
                $suiteConfig->getName(),
                $suiteConfig->hasSetting('type') ? $suiteConfig->getSetting('type') : null,
                $suiteConfig->getSettings()
            );
        }
    }
}
