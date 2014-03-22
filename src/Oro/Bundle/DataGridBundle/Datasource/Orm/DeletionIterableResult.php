<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * The aim of this class is provide an iterator which can be used for delete records.
 * This iterator is always iterates through the first page of a buffer. So, it allows you to
 * iterate through records to be deleted and delete them one by one.
 */
class DeletionIterableResult extends IterableResult
{
    public function __construct(QueryBuilder $source)
    {
        //in case if delete mass action param inset =0 (else frontend return coma separated ids)
        $source->setMaxResults(null);

        parent::__construct($source);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareQueryToExecute(Query $query)
    {
        parent::prepareQueryToExecute($query);

        // always iterate from the first record
        $query->setFirstResult(0);
    }
}
