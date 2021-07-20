<?php

namespace Oro\Bundle\BatchBundle\Step;

use Oro\Bundle\BatchBundle\Entity\StepExecution;

/**
 * Interface is used to receive StepExecution instance inside reader, processor or writer
 */
interface StepExecutionAwareInterface
{
    public function setStepExecution(StepExecution $stepExecution);
}
