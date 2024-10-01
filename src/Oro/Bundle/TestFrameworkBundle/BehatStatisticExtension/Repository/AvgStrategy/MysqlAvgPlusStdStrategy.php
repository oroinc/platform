<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\AvgStrategy;

use Doctrine\DBAL\Query\QueryBuilder;

class MysqlAvgPlusStdStrategy implements AvgStrategyInterface
{
    #[\Override]
    public function addSelect(QueryBuilder $builder)
    {
        $builder->addSelect('ROUND(AVG(time) + FORMAT(STD(time),2)) as time');
    }
}
