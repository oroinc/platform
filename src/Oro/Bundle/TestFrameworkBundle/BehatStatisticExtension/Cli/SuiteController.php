<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Cli;

use Behat\Testwork\Cli\Controller;
use Behat\Testwork\Suite\Suite;
use Behat\Testwork\Suite\SuiteRegistry;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\FeatureStatisticManager;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Suite\SuiteConfigurationRegistry;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides possibility to set the specific suite-set or suite. Configures suite registry with given suites collection.
 */
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
     * @var FeatureStatisticManager
     */
    protected $featureStatisticManager;

    public function __construct(
        SuiteConfigurationRegistry $suiteConfigRegistry,
        SuiteRegistry $behatSuiteRegistry,
        FeatureStatisticManager $featureStatisticManager
    ) {
        $this->suiteConfigRegistry = $suiteConfigRegistry;
        $this->behatSuiteRegistry = $behatSuiteRegistry;
        $this->featureStatisticManager = $featureStatisticManager;
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
        $tested = $input->getOption('available-suite-sets') ? [] : $this->featureStatisticManager->getTested();
        $skipped = [];

        $suiteName = $input->getOption('suite');
        if ($suiteName) {
            $suiteConfig = $this->suiteConfigRegistry->getSuiteConfig($suiteName);

            $skipped[] = $this->registerSuiteConfigs([$suiteConfig], $tested);
        }

        $suiteSet = $input->getOption('suite-set');
        if ($suiteSet) {
            $suiteConfigs = $this->suiteConfigRegistry->getSet($suiteSet);

            $skipped[] = $this->registerSuiteConfigs($suiteConfigs, $tested);
        }

        if (!$suiteName && !$suiteSet) {
            $suiteConfigs = $this->suiteConfigRegistry->getSuites();

            $skipped[] = $this->registerSuiteConfigs($suiteConfigs, $tested);
        }

        $skipped = \array_unique(\array_merge(...$skipped));
        foreach ($skipped as $path) {
            $parts = explode(DIRECTORY_SEPARATOR, $path);

            $output->writeln(sprintf('<info>Feature "%s" already tested and skipped.</info>', array_pop($parts)));
        }

        return $this->exitIfNoAvailableFeatures($input);
    }

    /**
     * @param Suite[] $suiteConfigs
     * @param array $tested
     * @return array
     */
    private function registerSuiteConfigs(array $suiteConfigs, array $tested)
    {
        $skipped = [[]];

        foreach ($suiteConfigs as $suiteConfig) {
            $settings = $suiteConfig->getSettings();
            $paths = array_filter(
                $settings['paths'],
                function (string $path) use ($tested) {
                    return !\in_array($path, $tested, true);
                }
            );

            $skipped[] = \array_diff($settings['paths'], $paths);
            $settings['paths'] = $paths;

            $this->behatSuiteRegistry->registerSuiteConfiguration(
                $suiteConfig->getName(),
                $suiteConfig->hasSetting('type') ? $suiteConfig->getSetting('type') : null,
                $settings
            );
        }

        return \array_merge(...$skipped);
    }

    /**
     * @param InputInterface $input
     * @return int|null
     */
    private function exitIfNoAvailableFeatures(InputInterface $input)
    {
        $suites = $this->behatSuiteRegistry->getSuites();
        if ($suites) {
            foreach ($suites as $key => $suite) {
                if ($suite instanceof Suite && !$suite->getSetting('paths')) {
                    unset($suites[$key]);
                }
            }
        }

        if (!$suites) {
            return 0;
        }

        return null;
    }
}
