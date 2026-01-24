<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository;

use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\StatisticModelInterface;

/**
 * Defines the contract for batch operations on test statistics.
 *
 * Implementations of this interface support adding statistic models to a collection
 * and flushing them to persistent storage in batch operations for efficiency.
 */
interface BatchRepositoryInterface
{
    /**
     * Add Model to collection
     */
    public function add(StatisticModelInterface $model);

    /**
     * Insert records in persistent layer
     * @return void
     */
    public function flush();
}
