<?php

namespace Oro\Bundle\BatchBundle\Step;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Step\ItemStep as BaseItemStep;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

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
        $this->initializeStepElements($stepExecution);

        $stepExecutor = new StepExecutor();
        $stepExecutor
            ->setReader($this->reader)
            ->setProcessor($this->processor)
            ->setWriter($this->writer);
        if (null !== $this->batchSize) {
            $stepExecutor->setBatchSize($this->batchSize);
        }

        $stepExecutor->execute($this);
        $this->flushStepElements();

        $this->restoreStepElements();
    }

    /**
     * {@inheritdoc}
     */
    public function handleWarning($element, $name, $reason, array $reasonParameters, $item)
    {
        $this->stepExecution->addWarning($name, $reason, $reasonParameters, $item);
        $this->dispatchInvalidItemEvent(get_class($element), $reason, $reasonParameters, $item);
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeStepElements(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
        foreach ($this->getConfigurableStepElements() as $element) {
            if ($element instanceof StepExecutionAwareInterface) {
                $element->setStepExecution($stepExecution);
            }
            if (method_exists($element, 'initialize')) {
                $element->initialize();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flushStepElements()
    {
        foreach ($this->getConfigurableStepElements() as $element) {
            if (method_exists($element, 'flush')) {
                $element->flush();
            }
        }
    }

    /**
     * Restores step elements to a state that was before a job execution.
     */
    protected function restoreStepElements()
    {
        foreach ($this->getConfigurableStepElements() as $element) {
            if ($element instanceof StepExecutionRestoreInterface) {
                $element->restoreStepExecution();
            }
        }
    }
}
