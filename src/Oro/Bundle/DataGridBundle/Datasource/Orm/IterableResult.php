<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

/**
 * Iterates query result with elements of ResultRecord type
 */
class IterableResult extends BufferedIdentityQueryResultIterator implements IterableResultInterface
{
    /**
     * {@inheritDoc}
     */
    public function next()
    {
        parent::next();

        $this->current = parent::current();
        if (null !== $this->current) {
            $this->current = new ResultRecord($this->current);
        }
    }
}
