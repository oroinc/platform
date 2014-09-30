<?php

namespace Oro\Bundle\BatchBundle\Step;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Step\ItemStep as BaseItemStep;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

/**
 * Basic step implementation that read items, process them and write them
 */
class ItemStep extends BaseItemStep implements StepExecutionWarningHandlerInterface
{
    /** @var int */
    protected $batchSize = null;

    /**
     * {@inheritdoc}
     */
    public function doExecute(StepExecution $stepExecution)
    {
        $this->initializeStepComponents($stepExecution);

        $stepExecutor = new StepExecutor();
        $stepExecutor
            ->setReader($this->reader)
            ->setProcessor($this->processor)
            ->setWriter($this->writer);

        /** @var JobExecution $jobExecution */
        $jobExecution      = $this->stepExecution->getJobExecution();
        $jobConfiguration  = $jobExecution->getJobInstance()->getRawConfiguration();
        $stepConfiguration = $jobConfiguration[$stepExecution->getStepName()];

        foreach ($stepConfiguration as $key => $value) {
            $stepExecution->getExecutionContext()->put($key, $value);
        }

        if (null !== $this->batchSize) {
            $stepExecutor->setBatchSize($this->batchSize);
        }

        $stepExecutor->execute($this);
    }

    /**
     * {@inheritdoc}
     */
    public function handleWarning($element, $name, $reason, array $reasonParameters, $item)
    {
        $this->stepExecution->addWarning($name, $reason, $reasonParameters, $item);
        $this->dispatchInvalidItemEvent(get_class($element), $reason, $reasonParameters, $item);
    }
}
