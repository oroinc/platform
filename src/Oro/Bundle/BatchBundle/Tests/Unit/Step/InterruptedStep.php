<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\AbstractStep;

/**
 * Step used for test and always declares a stopped execution
 */
class InterruptedStep extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function getConfiguration(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $config): void
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(StepExecution $stepExecution): void
    {
        $stepExecution->setTerminateOnly();
    }
}
