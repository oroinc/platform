<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\AvgStrategy;

/**
 * Defines the contract for classes that need to use an average calculation strategy.
 *
 * Classes implementing this interface can be injected with an AvgStrategyInterface
 * to customize how average test execution times are calculated from historical data.
 */
interface AvgStrategyAwareInterface
{
    public function setAvgStrategy(AvgStrategyInterface $strategy);
}
