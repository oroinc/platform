<?php

namespace Oro\Bundle\EntityMergeBundle\Datasource\Orm;

use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierWithoutOrderByIterationStrategy;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;

class MergeIterableResult extends IterableResult
{
    /**
     * {@inheritdoc}
     */
    protected function getIterationStrategy()
    {
        if (null === $this->iterationStrategy) {
            $this->iterationStrategy = new IdentifierWithoutOrderByIterationStrategy();
        }

        return $this->iterationStrategy;
    }
}
