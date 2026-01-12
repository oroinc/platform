<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

/**
 * Provides average test execution times from the master branch.
 *
 * This provider calculates average execution times based on historical data from the
 * master branch, providing a baseline for test suite distribution.
 */
class MasterAvgTimeProvider extends AbstractAvgTimeProvider
{
    #[\Override]
    protected function calculate()
    {
        $criteria = [
            'git_branch' => 'master',
        ];

        $this->averageTimeTable = $this->repository->getAverageTimeTable($criteria);
        $this->calculateAverageTime();
    }
}
