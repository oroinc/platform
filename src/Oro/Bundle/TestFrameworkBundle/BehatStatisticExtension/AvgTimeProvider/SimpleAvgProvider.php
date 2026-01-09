<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

/**
 * Provides average test execution times from all historical data.
 *
 * This provider calculates average execution times using all available historical data
 * without filtering by branch or other criteria, providing a simple baseline average.
 */
class SimpleAvgProvider extends AbstractAvgTimeProvider
{
    #[\Override]
    protected function calculate()
    {
        $this->averageTimeTable = $this->repository->getAverageTimeTable([]);
        $this->calculateAverageTime();
    }
}
