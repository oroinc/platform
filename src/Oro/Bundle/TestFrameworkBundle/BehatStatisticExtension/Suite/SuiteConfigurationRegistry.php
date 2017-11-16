<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Suite;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\Specification\SpecificationFinder;
use Behat\Testwork\Suite\Generator\SuiteGenerator;
use Behat\Testwork\Suite\GenericSuite;
use Behat\Testwork\Suite\Suite;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\FeaturePathLocator;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\SpecificationCountDivider;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\SuiteConfigurationDivider;

/**
 * It's decorate Behat\Testwork\Suite\SuiteRegistry
 * Because suiteConfiguration is an array and can't be modified by any part of application
 * This class encapsulate suiteConfiguration and provide methods for manipulate with it
 */
class SuiteConfigurationRegistry
{
    const PREFIX_SUITE_SET = 'AutoSuiteSet';

    /**
     * @var Suite[]
     */
    private $suites = [];

    /**
     * @var SpecificationFinder
     */
    private $specificationFinder;

    /**
     * @var SpecificationCountDivider
     */
    private $specificationDivider;

    /**
     * @var SuiteConfigurationDivider
     */
    private $suiteConfigurationDivider;

    /**
     * @var FeaturePathLocator
     */
    private $featurePathLocator;

    /**
     * @var array SuiteConfiguration grouped by sets
     */
    private $suiteSets = [];

    /**
     * @var SuiteGenerator[]
     */
    protected $suiteGenerators = [];

    public function __construct(
        SpecificationFinder $specificationFinder,
        SpecificationCountDivider $specificationDivider,
        SuiteConfigurationDivider $suiteConfigurationDivider,
        FeaturePathLocator $featurePathLocator
    ) {
        $this->specificationFinder = $specificationFinder;
        $this->specificationDivider = $specificationDivider;
        $this->suiteConfigurationDivider = $suiteConfigurationDivider;
        $this->featurePathLocator = $featurePathLocator;
    }

    /**
     * @return array
     */
    public function getSets()
    {
        return $this->suiteSets;
    }

    public function setSets(array $sets)
    {
        foreach ($sets as $setName => $suiteNames) {
            $this->suiteSets[$setName] = array_map(function ($suiteName) {
                if (!isset($this->suites[$suiteName])) {
                    throw new \RuntimeException(sprintf('Suite with "%s" name not found', $suiteName));
                }

                return $this->suites[$suiteName];
            }, $suiteNames);
        }
    }

    /**
     * @param $name
     * @return Suite[]
     * @throws \InvalidArgumentException in case if given suite set is not defined
     */
    public function getSet($name)
    {
        if (!isset($this->suiteSets[$name])) {
            throw new \InvalidArgumentException(sprintf('Suite set with "%s" name does not registered', $name));
        }

        return $this->suiteSets[$name];
    }

    /**
     * @param int $divider
     * @return void
     */
    public function divideSuites($divider)
    {
        foreach ($this->suites as $name => $suite) {
            $configs = $this->specificationDivider->divide($name, $suite->getSetting('paths'), $divider);

            foreach ($configs as $generatedSuiteName => $paths) {
                $config['settings'] = $suite->getSettings();
                $config['settings']['paths'] = $paths;

                $generatedSuite = $this->createSuite($generatedSuiteName, $config);
                $this->suites[$generatedSuiteName] = $generatedSuite;
            }

            unset($this->suites[$name]);
        }
    }

    /**
     * @param int $divider
     * @return void
     */
    public function generateSetsDividedByCount($divider)
    {
        $this->suiteSets = $this->specificationDivider->divide(
            self::PREFIX_SUITE_SET,
            $this->getSuites(),
            $divider
        );
    }

    /**
     * @param int $time
     * @return void
     */
    public function generateSetsByMaxExecutionTime($time)
    {
        $this->suiteSets = $this->suiteConfigurationDivider->divide(
            self::PREFIX_SUITE_SET,
            $this->getSuites(),
            $time
        );
    }

    /**
     * @return Suite[]
     */
    public function getSuites()
    {
        return $this->suites;
    }

    /**
     * @param string $name Suite name
     * @return Suite
     * @throws \InvalidArgumentException if suite name is not configured
     */
    public function getSuiteConfig($name)
    {
        if (!isset($this->suites[$name])) {
            throw new \InvalidArgumentException(sprintf(
                "Suite with '%s' name does not configured\n".
                "Configured suites: '%s'",
                $name,
                implode(', ', array_keys($this->suites))
            ));
        }

        return $this->suites[$name];
    }

    public function filterConfiguration()
    {
        foreach ($this->suites as $name => $suite) {
            $paths = $this->filterPaths($suite->getSetting('paths'));
            if (!$paths) {
                unset($this->suites[$name]);
            }

            $config['settings'] = $suite->getSettings();
            $config['settings']['paths'] = $paths;

            $suite = $this->createSuite($name, $config);
            $this->suites[$name] = $suite;
        }
    }

    /**
     * @param array $suites
     * @return void
     */
    public function setSuiteConfigurations(array $suites)
    {
        foreach ($suites as $name => $config) {
            $path = $this->filterPaths($config['settings']['paths']);

            if (!$path) {
                continue;
            }

            $config['settings']['paths'] = $path;
            $suite = $this->createSuite($name, $config);

            $this->suites[$name] = $suite;
        }
    }

    /**
     * @param SuiteGenerator $generator
     */
    public function addSuiteGenerator(SuiteGenerator $generator)
    {
        array_unshift($this->suiteGenerators, $generator);
    }

    /**
     * @param array $paths array paths either to directories with features or features
     * @return array
     */
    private function filterPaths(array $paths)
    {
        $suite = new GenericSuite('GenericSuite', ['paths' => $paths]);
        $iterators = $this->specificationFinder->findSuitesSpecifications([$suite]);
        $features = [];

        foreach ($iterators as $iterator) {
            /** @var FeatureNode $featureNode */
            foreach ($iterator as $featureNode) {
                $features[$featureNode->getFile()] = null;
            }
        }

        return array_keys($features);
    }

    /**
     * @param string $name
     * @param $config
     * @return Suite
     */
    private function createSuite($name, $config)
    {
        foreach ($this->suiteGenerators as $suiteGenerator) {
            $type = null;
            if (isset($config['type'])) {
                $type = $config['type'];
                // We save type in settings for regenerate suite from settings
                $config['settings']['type'] = $type;
            } elseif (isset($config['settings']['type'])) {
                $type = $config['settings']['type'];
            }

            if ($suiteGenerator->supportsTypeAndSettings($type, $config['settings'])) {
                return $suiteGenerator->generateSuite($name, $config['settings']);
            }
        }

        throw new \RuntimeException(sprintf(
            'Suite generator not found for "%s" suite and settings %s',
            $name,
            json_encode($config)
        ));
    }
}
