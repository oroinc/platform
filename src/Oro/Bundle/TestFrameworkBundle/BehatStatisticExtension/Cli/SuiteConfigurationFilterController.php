<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Suite\SuiteConfigurationRegistry;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This should be proceed after the Behat\Behat\Gherkin\Cli\FilterController
 */
class SuiteConfigurationFilterController implements Controller
{
    /**
     * @var SuiteConfigurationRegistry
     */
    private $suiteConfigRegistry;

    /**
     * @var array
     */
    private $suiteConfig;

    /**
     * @var array
     */
    private $sets;

    /**
     * @param SuiteConfigurationRegistry $suiteConfigRegistry
     * @param array $suiteConfig
     * @param array $sets
     */
    public function __construct(SuiteConfigurationRegistry $suiteConfigRegistry, array $suiteConfig, array $sets)
    {
        $this->suiteConfigRegistry = $suiteConfigRegistry;
        $this->suiteConfig = $suiteConfig;
        $this->sets = $sets;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->suiteConfigRegistry->setSuiteConfigurations($this->suiteConfig);
        $this->suiteConfigRegistry->filterConfiguration();
        $this->suiteConfigRegistry->setSets($this->sets);
    }
}
