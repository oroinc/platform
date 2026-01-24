<?php

namespace Oro\Bundle\EntityBundle\ORM\Repository;

use Doctrine\ORM\Internal\Hydration\IterableResult;

/**
 * Defines the contract for batch iterating over query results.
 *
 * Implementations of this interface provide a way to iterate over large result sets
 * in batches, which is useful for processing large amounts of data without loading
 * everything into memory at once.
 */
interface BatchIteratorInterface
{
    /**
     * @return IterableResult
     */
    public function getBatchIterator();
}
