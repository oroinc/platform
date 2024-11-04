<?php

namespace Oro\Bundle\BatchBundle\Step;

/**
 * Step implementation with CumulativeStepExecutor
 */
class CumulativeItemStep extends ItemStep
{
    #[\Override]
    protected function createStepExecutor(): StepExecutor
    {
        return new CumulativeStepExecutor();
    }
}
