<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\AvgStrategy;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Calculates simple average test execution time using MySQL.
 *
 * This strategy computes the average execution time from historical data using a basic
 * arithmetic mean, providing a straightforward estimate of expected test duration.
 */
class SimpleAvgStrategy implements AvgStrategyInterface
{
    #[\Override]
    public function addSelect(QueryBuilder $builder)
    {
        $builder->addSelect('ROUND(AVG(time)) as time');
    }
}
