<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\AvgStrategy;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Calculates average test execution time plus standard deviation using MySQL.
 *
 * This strategy computes the average execution time and adds the standard deviation,
 * providing a more conservative estimate that accounts for test execution variability.
 */
class MysqlAvgPlusStdStrategy implements AvgStrategyInterface
{
    #[\Override]
    public function addSelect(QueryBuilder $builder)
    {
        $builder->addSelect('ROUND(AVG(time) + FORMAT(STD(time),2)) as time');
    }
}
