<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

class WriterStub extends AbstractConfigurableStepElement implements ItemWriterInterface, StepExecutionAwareInterface
{
    /**
     * {@inheritdoc}
     */
    public function write(array $items)
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
