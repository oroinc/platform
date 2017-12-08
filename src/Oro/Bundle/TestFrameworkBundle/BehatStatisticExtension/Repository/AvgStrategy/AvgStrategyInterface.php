<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\AvgStrategy;

use Doctrine\DBAL\Query\QueryBuilder;

interface AvgStrategyInterface
{
    const TIME_FIELD_NAME = 'time';

    /**
     * @param QueryBuilder $builder
     */
    public function addSelect(QueryBuilder $builder);
}
