<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

/**
 * Defines the contract for providing average test execution times.
 *
 * Implementations of this interface retrieve historical test execution time data
 * and calculate average times for individual tests or overall test suites.
 */
interface AvgTimeProviderInterface
{
    /**
     * @param string|int $id
     * @return int|null
     */
    public function getAverageTimeById($id);

    /**
     * @return int
     */
    public function getAverageTime();
}
