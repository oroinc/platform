<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\StatisticRepository;

/**
 * Defines the contract for classes that need access to the test statistics repository.
 *
 * Classes implementing this interface can be injected with a StatisticRepository
 * to retrieve historical test execution data for average time calculations.
 */
interface StatisticRepositoryAwareInterface
{
    public function setRepository(StatisticRepository $repository);
}
