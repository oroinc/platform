<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

class TargetAvgTimeProvider extends AbstractAvgTimeProvider
{
    /**
     * {@inheritdoc}
     */
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
