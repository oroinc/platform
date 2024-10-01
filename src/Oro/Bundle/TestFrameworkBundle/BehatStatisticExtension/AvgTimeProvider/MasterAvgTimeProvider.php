<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

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
