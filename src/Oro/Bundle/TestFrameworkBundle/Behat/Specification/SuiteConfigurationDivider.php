<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Specification;

use Oro\Bundle\TestFrameworkBundle\Behat\Specification\Statistic\StatisticRepositoryInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\SuiteConfiguration;

class SuiteConfigurationDivider implements SpecificationDividerInterface
{
    /**
     * @var StatisticRepositoryInterface
     */
    protected $statisticRepository;

    /**
     * @param StatisticRepositoryInterface $statisticRepository
     */
    public function __construct(StatisticRepositoryInterface $statisticRepository)
    {
        $this->statisticRepository = $statisticRepository;
    }

    /**
     * @param $baseName
     * @param SuiteConfiguration[] $array
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
     * @param SuiteConfiguration[] $array
     * @return array
     */
    protected function getSuiteDurations(array $array)
    {
        $suiteDuration = [];

        foreach ($array as $configuration) {
            $suiteDuration[$configuration->getName()] = 0;
            foreach ($configuration->getPaths() as $path) {
                $suiteDuration[$configuration->getName()] += $this->statisticRepository->getFeatureDuration($path);
            }
        }

        return $suiteDuration;
    }
}
