<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

/**
 * Provides average test execution times from the target branch of a pull request.
 *
 * This provider calculates average execution times based on historical data from the
 * target branch (e.g., master), useful for comparing PR test times against the baseline.
 */
class TargetAvgTimeProvider extends AbstractAvgTimeProvider
{
    #[\Override]
    protected function calculate()
    {
        if (!$this->criteria->containsKey('target_branch')) {
            return;
        }

        $criteria = [
            'git_branch' => $this->criteria->get('target_branch'),
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
