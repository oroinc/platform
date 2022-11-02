<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\StatisticRepository;

interface StatisticRepositoryAwareInterface
{
    public function setRepository(StatisticRepository $repository);
}
