<?php

namespace Oro\Bundle\BatchBundle\Step;

use Akeneo\Bundle\BatchBundle\Step\ItemStep as BaseItemStep;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

/**
 * Basic step implementation that read items, process them and write them
 *
 */
class ItemStep extends BaseItemStep implements StepExecutionWarningHandlerInterface
{
    /**
     * @var int
     */
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
        if (null !== $this->batchSize) {
            $stepExecutor->setBatchSize($this->batchSize);
        }

        $stepExecutor->execute($this);
    }

    /**
     * {@inheritdoc}
     */
    public function handleWarning($element, $name, $reason, $item)
    {
        $this->stepExecution->addWarning($name, $reason, $item);
        $this->dispatchInvalidItemEvent(get_class($element), $reason, $item);
    }
}
