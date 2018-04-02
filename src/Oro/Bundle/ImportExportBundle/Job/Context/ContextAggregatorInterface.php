<?php

namespace Oro\Bundle\ImportExportBundle\Job\Context;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

/**
 * Provides an interface for classes responsible to aggregate the execution context
 * of steps into one ContextInterface object.
 */
interface ContextAggregatorInterface
{
    /**
     * Aggregates the execution context of all steps into one ContextInterface object.
     *
     * @param JobExecution $jobExecution
     *
     * @return ContextInterface|null
     */
    public function getAggregatedContext(JobExecution $jobExecution);

    /**
     * Gets the type of the aggregator.
     *
     * @return string
     */
    public function getType();
}
