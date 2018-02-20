<?php

namespace Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction;

use Doctrine\ORM\Query;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Extension\MassAction\IterableResultFactory;
use Oro\Bundle\EntityMergeBundle\Datasource\Orm\MergeIterableResult;

class MergeIterableResultFactory extends IterableResultFactory
{
    /**
     * {@inheritdoc}
     */
    protected function getIterableResult($qb): IterableResult
    {
        return new MergeIterableResult($qb);
    }
}
