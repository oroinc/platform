<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\Statistic;

/**
 * Defines the contract for retrieving test execution statistics.
 *
 * Implementations of this interface provide access to historical test execution data,
 * such as feature duration, for use in test suite distribution and optimization.
 */
interface StatisticRepositoryInterface
{
    /**
     * @param string $path path to the feature file
     * @return int Time in seconds
     */
    public function getFeatureDuration($path);
}
