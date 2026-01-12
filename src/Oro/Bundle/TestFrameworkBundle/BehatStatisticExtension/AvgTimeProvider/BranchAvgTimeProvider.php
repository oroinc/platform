<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

/**
 * Provides average test execution times for a specific Git branch.
 *
 * This provider calculates average execution times based on historical data from builds
 * on a specific Git branch, allowing test suite distribution to be optimized per branch.
 */
class BranchAvgTimeProvider extends AbstractAvgTimeProvider
{
    #[\Override]
    protected function calculate()
    {
        $branch = $this->criteria->get('branch_name') ?: $this->criteria->get('single_branch_name');

        if (!$branch) {
            return;
        }

        $criteria = [
            'git_branch' => $branch,
        ];

        $buildIds = $this->repository->getLastBuildIds(
            $this->criteria->get('count_build_limit'),
            $criteria
        );

        if (empty($buildIds)) {
            return;
        }

        $criteria['build_id'] = $buildIds;

        $this->averageTimeTable = $this->repository->getAverageTimeTable($criteria);
        $this->calculateAverageTime();
    }
}
