<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

class SimpleAvgProvider extends AbstractAvgTimeProvider
{
    #[\Override]
    protected function calculate()
    {
        $this->averageTimeTable = $this->repository->getAverageTimeTable([]);
        $this->calculateAverageTime();
    }
}
