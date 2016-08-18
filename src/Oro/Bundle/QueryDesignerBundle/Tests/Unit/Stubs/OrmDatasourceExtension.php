<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Stubs;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\QueryDesignerBundle\Grid\Extension\OrmDatasourceExtension as BaseExtension;

class OrmDatasourceExtension extends BaseExtension
{
    /**
     * {@inheritdoc}
     */
    protected function createDatasourceAdapter(QueryBuilder $qb)
    {
        return new GroupingOrmFilterDatasourceAdapter($qb);
    }
}
