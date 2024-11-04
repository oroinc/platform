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
    #[\Override]
    public function process($item)
    {
    }

    #[\Override]
    public function setStepExecution(StepExecution $stepExecution)
    {
    }

    #[\Override]
    public function getConfigurationFields(): array
    {
        return [];
    }
}
