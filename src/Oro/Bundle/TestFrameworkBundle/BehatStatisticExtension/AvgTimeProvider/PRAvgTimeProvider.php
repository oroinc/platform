<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

/**
 * Provides average test execution times for pull request branches.
 *
 * This provider calculates average execution times based on historical data from builds
 * on a pull request branch and its target branch, enabling optimized test distribution for PRs.
 */
class PRAvgTimeProvider extends AbstractAvgTimeProvider
{
    #[\Override]
    protected function calculate()
    {
        if (!$this->criteria->containsKey('branch_name') || !$this->criteria->containsKey('target_branch')) {
            return;
        }

        $criteria = [
            'git_branch' => $this->criteria->get('branch_name'),
            'git_target' => $this->criteria->get('target_branch'),
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
