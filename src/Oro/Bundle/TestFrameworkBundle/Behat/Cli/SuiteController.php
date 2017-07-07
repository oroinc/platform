<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Behat\Testwork\Suite\Exception\SuiteConfigurationException;
use Behat\Testwork\Suite\Exception\SuiteNotFoundException;
use Behat\Testwork\Suite\SuiteRegistry;
use Behat\Testwork\Suite\SuiteRepository;
use Oro\Bundle\TestFrameworkBundle\Behat\Specification\SpecificationDivider;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class SuiteController implements Controller
{
    /**
     * @var SuiteRegistry
     */
    private $registry;

    /**
     * @var array
     */
    private $suiteConfigurations = [];

    /**
     * @var SpecificationDivider
     */
    private $divider;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * Initializes controller.
     *
     * @param SuiteRegistry $registry
     * @param array $suiteConfigurations
     */
    public function __construct(
        SuiteRegistry $registry,
        array $suiteConfigurations,
        SpecificationDivider $divider,
        KernelInterface $kernel
    ) {
        $this->registry = $registry;
        $this->suiteConfigurations = $suiteConfigurations;
        $this->divider = $divider;
        $this->kernel = $kernel;
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
     * @throws SuiteNotFoundException in case when suite name provided by suite option is not configured
     * @throws SuiteConfigurationException It should be never happen until someone configure suite with <bundle>#0 name
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $exerciseSuiteName = $input->getOption('suite');
        $suiteConfigurations = $this->getSuiteConfigurations($input->getOption('suite-divider'));

        if (null !== $exerciseSuiteName && !isset($suiteConfigurations[$exerciseSuiteName])) {
            throw new SuiteNotFoundException(sprintf(
                '`%s` suite is not found or has not been properly registered.',
                $exerciseSuiteName
            ), $exerciseSuiteName);
        }

        foreach ($suiteConfigurations as $name => $config) {
            if (null !== $exerciseSuiteName && $exerciseSuiteName !== $name) {
                continue;
            }

            $this->registry->registerSuiteConfiguration(
                $name,
                $config['type'],
                $config['settings']
            );
        }
    }

    /**
     * @param int $divideNumber
     * @return array
     */
    private function getSuiteConfigurations($divideNumber)
    {
        if (null === $divideNumber) {
            return $this->suiteConfigurations;
        }

        $suiteConfigurations = [];

        foreach ($this->suiteConfigurations as $name => $config) {
            try {
                $this->kernel->getBundle($name);
                $type = 'symfony_bundle';
                $bundleName = $name;
            } catch (\InvalidArgumentException $e) {
                $type = null;
                $bundleName = null;
            }
            $dividedConfiguration = $this->divider->divideSuite($name, $config['settings']['paths'], $divideNumber);
            foreach ($dividedConfiguration as $generatedSuiteName => $paths) {
                $suiteConfig = $config;
                $suiteConfig['type'] = $type;
                $suiteConfig['settings']['paths'] = $paths;
                $suiteConfig['settings']['bundle'] = $bundleName;
                $suiteConfigurations[$generatedSuiteName] = $suiteConfig;
            }
        }

        return $suiteConfigurations;
    }
}
