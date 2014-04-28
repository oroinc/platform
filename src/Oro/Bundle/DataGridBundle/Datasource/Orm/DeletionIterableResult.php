<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Doctrine\ORM\Query;

/**
 * The aim of this class is provide an iterator which can be used for delete records.
 * This iterator is always iterates through the first page of a buffer. So, it allows you to
 * iterate through records to be deleted and delete them one by one.
 */
class DeletionIterableResult extends IterableResult
{
    /**
     * {@inheritdoc}
     */
    protected function prepareQueryToExecute(Query $query)
    {
        // always iterate from the first record
        $query->setFirstResult(0);
    }
}
