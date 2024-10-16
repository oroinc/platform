<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

class ReaderStub extends AbstractConfigurableStepElement implements ItemReaderInterface, StepExecutionAwareInterface
{
    #[\Override]
    public function read()
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
