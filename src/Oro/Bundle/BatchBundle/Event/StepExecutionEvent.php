<?php

namespace Oro\Bundle\BatchBundle\Event;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event triggered during stepExecution execution
 */
class StepExecutionEvent extends Event implements EventInterface
{
    private StepExecution $stepExecution;

    public function __construct(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    public function getStepExecution(): StepExecution
    {
        return $this->stepExecution;
    }
}
