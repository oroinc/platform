<?php

namespace Oro\Bundle\BatchBundle\Step;

/**
 * Step implementation with CumulativeStepExecutor
 */
class CumulativeItemStep extends ItemStep
{
    protected function createStepExecutor(): StepExecutor
    {
        return new CumulativeStepExecutor();
    }
}
