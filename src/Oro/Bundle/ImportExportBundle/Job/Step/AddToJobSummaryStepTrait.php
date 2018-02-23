<?php

namespace Oro\Bundle\ImportExportBundle\Job\Step;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Job\Context\SelectiveContextAggregator;

/**
 * This trait can be used in steps support "add_to_job_summary" parameter.
 * @see \Oro\Bundle\ImportExportBundle\Job\Context\SelectiveContextAggregator
 */
trait AddToJobSummaryStepTrait
{
    /** @var bool */
    private $addToJobSummary = false;

    /**
     * Indicates whether or not step context statistics should be included in the summarized job context
     *
     * @return bool
     */
    public function isAddToJobSummary()
    {
        return $this->addToJobSummary;
    }

    /**
     * @param bool $addToJobSummary
     */
    public function setAddToJobSummary($addToJobSummary)
    {
        $this->addToJobSummary = $addToJobSummary;
    }

    /**
     * Adds "add_to_job_summary" option to the step execution
     * if this step execution summary need to be added to job execution summary.
     *
     * @param StepExecution $stepExecution
     */
    protected function addToJobSummaryToStepExecution(StepExecution $stepExecution)
    {
        if ($this->isAddToJobSummary()) {
            $stepExecution->getExecutionContext()->put(SelectiveContextAggregator::STEP_PARAMETER_NAME, true);
        }
    }
}
