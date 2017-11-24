<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\StatisticRepository;

interface StatisticRepositoryAwareInterface
{
    /**
     * @param StatisticRepository $repository
     */
    public function setRepository(StatisticRepository $repository);
}
