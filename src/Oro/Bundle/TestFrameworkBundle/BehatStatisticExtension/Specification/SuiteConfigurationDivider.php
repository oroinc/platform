<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification;

use Behat\Testwork\Suite\Suite;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider\FeatureAvgTimeRegistry;

/**
 * Divides test suites into sets based on maximum execution time.
 *
 * This divider groups suites into sets such that each set's total execution time
 * does not exceed a specified limit, enabling balanced parallel test execution based
 * on historical execution time data.
 */
class SuiteConfigurationDivider implements SpecificationDividerInterface
{
    /**
     * @var FeatureAvgTimeRegistry
     */
    protected $featureAvgTimeRegistry;

    /**
     * @var FeaturePathLocator
     */
    protected $featurePathLocator;

    public function __construct(
        FeatureAvgTimeRegistry $featureAvgTimeRegistry,
        FeaturePathLocator $featurePathLocator
    ) {
        $this->featureAvgTimeRegistry = $featureAvgTimeRegistry;
        $this->featurePathLocator = $featurePathLocator;
    }

    /**
     * @param $baseName
     * @param Suite[] $array
     * @param int $divider limit in seconds
     * @return array
     */
    #[\Override]
    public function divide($baseName, array $array, $divider)
    {
        $suiteDuration = $this->getSuiteDurations($array);
        arsort($suiteDuration);
        $setDuration = [];
        $generatedSets = [];
        $index = 0;

        while (count($suiteDuration)) {
            $setName = $baseName.'_'.$index;
            if (!isset($generatedSets[$setName])) {
                $setDuration[$setName] = reset($suiteDuration);
                $generatedSets[$setName][] = $array[key($suiteDuration)];
                //remove suite
                array_shift($suiteDuration);
            }

            if ($divider <= $setDuration[$setName]) {
                $index++;
                continue;
            }

            foreach ($suiteDuration as $suite => $duration) {
                if ($divider >= $setDuration[$setName] + $duration) {
                    $setDuration[$setName] += $duration;
                    $generatedSets[$setName][] = $array[$suite];
                    unset($suiteDuration[$suite]);
                }
            }

            $index++;
        }

        return $generatedSets;
    }

    /**
     * @param Suite[] $array
     * @return array
     */
    protected function getSuiteDurations(array $array)
    {
        $suiteDuration = [];

        foreach ($array as $suite) {
            $suiteDuration[$suite->getName()] = 0;
            foreach ($suite->getSetting('paths') as $path) {
                $suiteDuration[$suite->getName()] += $this->getFeatureDuration($path);
            }
        }

        return $suiteDuration;
    }

    /**
     * @param string $path
     * @return int
     */
    protected function getFeatureDuration($path)
    {
        $relativePath = $this->featurePathLocator->getRelativePath($path);

        return $this->featureAvgTimeRegistry->getAverageTimeById($relativePath);
    }
}
