<?php

namespace Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Extension\MassAction\IterableResultFactory;
use Oro\Bundle\EntityMergeBundle\Datasource\Orm\MergeIterableResult;

/**
 * Creates merge-specific iterable result instances for processing large datasets.
 *
 * Extends the base {@see IterableResultFactory} to provide a specialized iterable result
 * that uses merge-specific iteration strategies for efficient batch processing of
 * entities during merge operations.
 */
class MergeIterableResultFactory extends IterableResultFactory
{
    #[\Override]
    protected function getIterableResult($qb): IterableResult
    {
        return new MergeIterableResult($qb);
    }
}
