<?php

namespace Oro\Bundle\BatchBundle\ORM\Query;

use Doctrine\ORM\Query;

/**
 * Iterates results of Query for deletion queries, i.e. without first result shifting
 */
class DeletionQueryResultIterator extends BufferedQueryResultIterator
{
    /**
     * {@inheritdoc}
     */
    protected function prepareQueryToExecute(Query $query)
    {
        // always iterate from the first record
        $query->setFirstResult($this->firstResult);
    }
}
