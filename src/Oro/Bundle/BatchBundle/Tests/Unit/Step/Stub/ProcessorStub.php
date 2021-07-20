<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Oro\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

class ProcessorStub extends AbstractConfigurableStepElement implements
    ItemProcessorInterface,
    StepExecutionAwareInterface
{
    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
    }

    public function setStepExecution(StepExecution $stepExecution)
    {
    }

    public function getConfigurationFields(): array
    {
        return [];
    }
}
