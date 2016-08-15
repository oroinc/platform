<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Behat\Testwork\Suite\Exception\SuiteNotFoundException;
use Behat\Testwork\Suite\SuiteRegistry;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var array
     */
    private $applicationSuites = [];

    /**
     * Initializes controller.
     *
     * @param SuiteRegistry $registry
     * @param array $suiteConfigurations
     * @param array $applicationSuites
     */
    public function __construct(SuiteRegistry $registry, array $suiteConfigurations, array $applicationSuites)
    {
        $this->registry = $registry;
        $this->suiteConfigurations = $suiteConfigurations;
        $this->applicationSuites = $applicationSuites;
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
                '--applicable-suites',
                null,
                InputOption::VALUE_NONE,
                'Run test suites that was configured with application_suites config option'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->getRequestedSuites($input) as $name => $config) {
            $this->registry->registerSuiteConfiguration(
                $name,
                $config['type'],
                $config['settings']
            );
        }
    }

    /**
     * Get suites list according to parameters from console e.g. --suite, --applicable-suites
     *
     * @param InputInterface $input
     * @return array
     */
    protected function getRequestedSuites(InputInterface $input)
    {
        $onlyApplicable = $input->getOption('applicable-suites');
        $exerciseSuiteName = $input->getOption('suite');

        if (null !== $exerciseSuiteName) {
            return $this->getSuitesByNames([$exerciseSuiteName]);
        } elseif ($onlyApplicable) {
            return $this->getSuitesByNames($this->applicationSuites);
        } else {
            return $this->suiteConfigurations;
        }
    }

    /**
     * @param array $suitesNames
     * @return array
     */
    protected function getSuitesByNames(array $suitesNames)
    {
        $suites = [];

        foreach ($suitesNames as $suitesName) {
            if (!isset($this->suiteConfigurations[$suitesName])) {
                throw new SuiteNotFoundException(sprintf(
                    '`%s` suite is not found or has not been properly registered.',
                    $suitesName
                ), $suitesName);
            }

            $suites[$suitesName] = $this->suiteConfigurations[$suitesName];
        }

        return $suites;
    }
}
