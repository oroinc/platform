<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\AvgStrategy;

use Doctrine\DBAL\Query\QueryBuilder;

class SimpleAvgStrategy implements AvgStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function addSelect(QueryBuilder $builder)
    {
        $builder->addSelect('ROUND(AVG(time)) as time');
    }
}
