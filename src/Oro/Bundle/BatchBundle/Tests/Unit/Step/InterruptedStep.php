<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\AbstractStep;

/**
 * Step used for test and always declares a stopped execution
 */
class InterruptedStep extends AbstractStep
{
    #[\Override]
    public function getConfiguration(): array
    {
        return [];
    }

    #[\Override]
    public function setConfiguration(array $config): void
    {
    }

    #[\Override]
    protected function doExecute(StepExecution $stepExecution): void
    {
        $stepExecution->setTerminateOnly();
    }
}
