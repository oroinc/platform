<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Suite;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\Specification\SpecificationFinder;
use Behat\Testwork\Suite\GenericSuite;
use Oro\Bundle\TestFrameworkBundle\Behat\Specification\SpecificationDivider;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * It's decorate Behat\Testwork\Suite\SuiteRegistry
 * Because suiteConfiguration is an array and can't be modified by any part of application
 * This class encapsulate suiteConfiguration and provide methods for manipulate with it
 */
class SuiteConfigurationRegistry
{
    const SUITE_TYPE_SYMFONY = 'symfony_bundle';
    const SUITE_TYPE_GENERIC = null;

    const PREFIX_SUITE_SET = 'AutoSuiteSet';

    /**
     * @var SuiteConfiguration[]
     */
    private $suiteConfigurations;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var SpecificationFinder
     */
    private $specificationFinder;

    /**
     * @var SpecificationDivider
     */
    private $specificationDivider;

    /**
     * @var array SuiteConfiguration grouped by sets
     */
    private $suiteSets = [];

    public function __construct(
        KernelInterface $kernel,
        SpecificationFinder $specificationFinder,
        SpecificationDivider $specificationDivider
    ) {
        $this->kernel = $kernel;
        $this->specificationFinder = $specificationFinder;
        $this->specificationDivider = $specificationDivider;
    }

    /**
     * @return array
     */
    public function getSets()
    {
        return $this->suiteSets;
    }

    /**
     * @param $name
     * @return SuiteConfiguration[]
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
     */
    public function divideSuites($divider)
    {
        foreach ($this->suiteConfigurations as $name => $baseSuiteConfig) {
            $configs = $this->specificationDivider->divide($name, $baseSuiteConfig->getPaths(), $divider);

            foreach ($configs as $generatedSuiteName => $paths) {
                $suiteConfig = clone $baseSuiteConfig;
                $suiteConfig
                    ->setSetting('paths', $paths)
                    ->setName($generatedSuiteName)
                ;
                $this->suiteConfigurations[$generatedSuiteName] = $suiteConfig;
            }

            unset($this->suiteConfigurations[$name]);
        }
    }

    public function genererateSets($divider)
    {
        $this->suiteSets = $this->specificationDivider->divide(
            self::PREFIX_SUITE_SET,
            $this->getSuiteConfigurations(),
            $divider
        );
    }

    /**
     * @return SuiteConfiguration[]
     */
    public function getSuiteConfigurations()
    {
        return $this->suiteConfigurations;
    }

    /**
     * @param string $name Suite name
     * @throws \InvalidArgumentException if suite name is not configured
     */
    public function getSuiteConfig($name)
    {
        if (!isset($this->suiteConfigurations[$name])) {
            throw new \InvalidArgumentException(sprintf(
                "Suite with '%s' name does not configured\n".
                "Configured suites: '%s'",
                $name,
                implode(', ', array_keys($this->suiteConfigurations))
            ));
        }

        return $this->suiteConfigurations[$name];
    }

    /**
     * @param array $suiteConfigurations
     */
    public function setSuiteConfigurations(array $suiteConfigurations)
    {
        foreach ($suiteConfigurations as $name => $config) {
            if (!$paths = $this->filterPaths($config['settings']['paths'])) {
                continue;
            }

            $suiteConfig = $this->createSuiteConfig($name, $config['settings']);
            $suiteConfig->setSetting('paths', $paths);

            $this->suiteConfigurations[$name] = $suiteConfig;
        }
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
     * @return SuiteConfiguration
     */
    private function createSuiteConfig($name, $settings)
    {
        $suiteConfig = new SuiteConfiguration($name);
        $suiteConfig->setSettings($settings);

        try {
            $this->kernel->getBundle($name);
            $suiteConfig
                ->setType(self::SUITE_TYPE_SYMFONY)
                ->setSetting('bundle', $name)
            ;
        } catch (\InvalidArgumentException $e) {
            $suiteConfig->setType(self::SUITE_TYPE_GENERIC);
        }

        return $suiteConfig;
    }
}
