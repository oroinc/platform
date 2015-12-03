<?php

namespace Oro\Bundle\BatchBundle\Event;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event triggered during stepExecution execution
 *
 */
class StepExecutionEvent extends Event implements EventInterface
{
    protected $stepExecution;

    public function __construct(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    public function getStepExecution()
    {
        return $this->stepExecution;
    }
}
