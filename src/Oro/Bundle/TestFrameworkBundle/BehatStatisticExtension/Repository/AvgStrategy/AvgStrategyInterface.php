<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\AvgStrategy;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Defines the contract for strategies that calculate average test execution times.
 *
 * Implementations of this interface provide different algorithms for computing average
 * execution times from historical test data, allowing customization of time calculations
 * (e.g., simple average, average plus standard deviation).
 */
interface AvgStrategyInterface
{
    public const TIME_FIELD_NAME = 'time';

    public function addSelect(QueryBuilder $builder);
}
