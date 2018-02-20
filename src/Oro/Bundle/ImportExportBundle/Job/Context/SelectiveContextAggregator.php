<?php

namespace Oro\Bundle\ImportExportBundle\Job\Context;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Job\ContextHelper;

/**
 * Creates the context object contains counters summarized by the type from all steps
 * marked as "add_to_job_summary".
 */
class SelectiveContextAggregator implements ContextAggregatorInterface
{
    const TYPE = 'selective';

    /**
     * The name of parameter that should be added to a step parameters
     * to add the step summary to the result context object.
     */
    const STEP_PARAMETER_NAME = 'add_to_job_summary';

    /** @var ContextRegistry */
    protected $contextRegistry;

    /**
     * @param ContextRegistry $contextRegistry
     */
    public function __construct(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregatedContext(JobExecution $jobExecution)
    {
        /** @var ContextInterface $context */
        $context = null;
        $stepExecutions = $jobExecution->getStepExecutions();
        foreach ($stepExecutions as $stepExecution) {
            if ($stepExecution->getExecutionContext()->get(self::STEP_PARAMETER_NAME)) {
                if (null === $context) {
                    $context = $this->contextRegistry->getByStepExecution($stepExecution);
                } else {
                    ContextHelper::mergeContextCounters(
                        $context,
                        $this->contextRegistry->getByStepExecution($stepExecution)
                    );
                }
            }
        }

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
