<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Cli;

use Behat\Testwork\Cli\Controller;
use Behat\Testwork\Suite\Suite;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider\FeatureAvgTimeRegistry;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\FeaturePathLocator;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Suite\SuiteConfigurationRegistry;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AvailableSuiteSetsController implements Controller
{
    /**
     * @var SuiteConfigurationRegistry
     */
    private $suiteConfigRegistry;

    /**
     * @var FeatureAvgTimeRegistry
     */
    private $featureAvgTimeRegistry;

    /**
     * @var FeaturePathLocator
     */
    private $featurePathLocator;

    /**
     * @param SuiteConfigurationRegistry $suiteConfigRegistry
     * @param FeatureAvgTimeRegistry $featureAvgTimeProvider
     * @param FeaturePathLocator $featurePathLocator
     */
    public function __construct(
        SuiteConfigurationRegistry $suiteConfigRegistry,
        FeatureAvgTimeRegistry $featureAvgTimeProvider,
        FeaturePathLocator $featurePathLocator
    ) {
        $this->suiteConfigRegistry = $suiteConfigRegistry;
        $this->featureAvgTimeRegistry = $featureAvgTimeProvider;
        $this->featurePathLocator = $featurePathLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption(
                '--available-suite-sets',
                null,
                InputOption::VALUE_NONE,
                'Output all available suite sets'
            )
            ->addOption(
                '--dump-suite-sets',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to json file with suite sets'
            )
            ->addOption(
                '--dump-suites',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to json file with suites'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($path = $input->getOption('dump-suite-sets')) {
            $this->dumpSuiteSets($path);
        }

        if ($path = $input->getOption('dump-suites')) {
            $this->dumpSuites($path);
        }

        if (!$input->getOption('available-suite-sets')) {
            return;
        }

        /**
         * @var string $setName
         * @var Suite[] $suites
         */
        foreach ($this->suiteConfigRegistry->getSets() as $setName => $suites) {
            $output->writeln($setName);

            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                foreach ($suites as $suite) {
                    $output->writeln(sprintf('    %s', $suite->getName()));

                    if ($output->getVerbosity() > OutputInterface::VERBOSITY_VERBOSE) {
                        $this->printFeatures($output, $suite->getSetting('paths'));
                    }
                }
            }
        }

        return 0;
    }

    /**
     * @param string $path
     */
    private function dumpSuiteSets($path)
    {
        $this->createDirectory($path);

        $sets = [];
        foreach ($this->suiteConfigRegistry->getSets() as $setName => $suites) {
            $sets[$setName] = array_map(function (Suite $suite) {
                return $suite->getName();
            }, $suites);
        }

        file_put_contents($path, json_encode($sets));
    }

    /**
     * @param string $path
     */
    private function dumpSuites($path)
    {
        $this->createDirectory($path);

        $suites = [];
        foreach ($this->suiteConfigRegistry->getSuites() as $suite) {
            $suites[$suite->getName()]['settings'] = $suite->getSettings();
        }

        file_put_contents($path, json_encode($suites));
    }

    /**
     * @param string $path
     */
    private function createDirectory($path)
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        if (!is_dir($dir)) {
            mkdir($dir);
        }
    }

    /**
     * @param OutputInterface $output
     * @param array $paths
     */
    private function printFeatures(OutputInterface $output, array $paths)
    {
        foreach ($paths as $path) {
            $relativePath = $this->featurePathLocator->getRelativePath($path);
            $time = $this->featureAvgTimeRegistry->getAverageTimeById($relativePath);

            $output->writeln(sprintf('        %s - %s sec', $path, $time));
        }
    }
}
