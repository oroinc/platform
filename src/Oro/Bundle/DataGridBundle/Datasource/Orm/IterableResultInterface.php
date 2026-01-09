<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * Defines the contract for iterable ORM query results.
 *
 * This interface extends the standard Iterator interface to provide memory-efficient iteration
 * over large ORM query result sets. It supports buffering strategies to optimize resource usage
 * when processing large datasets that would otherwise consume excessive memory.
 */
interface IterableResultInterface extends \Iterator
{
    /**
     * Sets buffer size that can be used to optimize resources usage during iterations
     *
     * @param int $size
     */
    public function setBufferSize($size);

    /**
     * @return Query|QueryBuilder|object
     */
    public function getSource();
}
