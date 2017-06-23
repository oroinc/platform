<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Specification;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\Specification\SpecificationFinder;
use Behat\Testwork\Suite\Exception\SuiteConfigurationException;
use Behat\Testwork\Suite\Generator\GenericSuiteGenerator;
use Behat\Testwork\Suite\Suite;
use Behat\Testwork\Suite\SuiteRegistry;
use Guzzle\Iterator\ChunkedIterator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class SpecificationDivider
{
    /**
     * Divide suite by features count
     * Each generated suite will has number of features
     * E.g. if 'AcmeSuite' suite has 13 features and divider is 5,
     *      3 suites will be generated
     *      'AcmeSuite#1' and 'AcmeSuite#2' with 5 features each
     *      and 'AcmeSuite#3' with 3 features
     *
     * @param string $suiteName e.g. AcmeSuite
     * @param array $paths Paths to feature files or directories with feature files
     * @param int $divider
     * @return array [
     *                 'AcmeSuite#1' => ['/path/to/first.feature', '/path/to/second.feature],
     *                 'AcmeSuite#2' => ['/path/to/third.feature'],
     *               ]
     *
     * @throws SuiteConfigurationException It should be never happen until someone call suite with <bundle>#1
     */
    public function divideSuite($suiteName, array $paths, $divider)
    {
        $generatedSuites = [];

        $featureFiles = $this->getFeaturesList($paths);
        $chunks = array_chunk((array) $featureFiles, $divider);

        foreach ($chunks as $index => $chunk) {
            $generatedSuiteName = $suiteName.'#'.$index;
            $generatedSuites[$generatedSuiteName] = $chunk;
        }

        return $generatedSuites;
    }

    /**
     * @param array $paths
     * @return \Iterator|SplFileInfo[] array of features absolute paths
     */
    private function getFeaturesList(array $paths)
    {
        $finder = Finder::create()->files()->name('*.feature');

        foreach ($paths as $path) {
            $finder->in($path);
        }

        $files = [];

        foreach ($finder->getIterator() as $file) {
            $files[] = $file->getRealPath();
        }

        return array_unique($files);
    }
}
