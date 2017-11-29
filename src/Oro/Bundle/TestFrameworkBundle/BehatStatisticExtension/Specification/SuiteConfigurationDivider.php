<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification;

use Behat\Testwork\Suite\Suite;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider\FeatureAvgTimeRegistry;

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

    /**
     * @param FeatureAvgTimeRegistry $featureAvgTimeRegistry
     * @param FeaturePathLocator $featurePathLocator
     */
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
