<?php

namespace Oro\Bundle\EntityMergeBundle\Datasource\Orm;

use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierWithoutOrderByIterationStrategy;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;

/**
 * Provides merge-optimized iteration over ORM query results.
 *
 * Extends the base IterableResult to use a specialized iteration strategy that processes
 * entities without requiring `ORDER BY` clauses, which is more efficient for merge operations
 * that need to iterate over large result sets while identifying entities by their identifiers.
 */
class MergeIterableResult extends IterableResult
{
    #[\Override]
    protected function getIterationStrategy()
    {
        if (null === $this->iterationStrategy) {
            $this->iterationStrategy = new IdentifierWithoutOrderByIterationStrategy();
        }

        return $this->iterationStrategy;
    }
}
