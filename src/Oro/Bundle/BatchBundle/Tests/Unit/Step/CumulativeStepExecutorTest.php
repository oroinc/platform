<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step;

use Oro\Bundle\BatchBundle\Step\CumulativeStepExecutor;
use Oro\Bundle\BatchBundle\Step\StepExecutor;
use Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub\Processor;
use Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub\Reader;
use Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub\Writer;

class CumulativeStepExecutorTest extends StepExecutorTest
{
    private function getStepExecutor(array $items = []): StepExecutor
    {
        $stepExecutor = new CumulativeStepExecutor();
        $reader = new Reader($items);
        $writer = new Writer();
        $processor = new Processor();

        $stepExecutor->setBatchSize(1);
        $stepExecutor->setReader($reader);
        $stepExecutor->setWriter($writer);
        $stepExecutor->setProcessor($processor);

        return $stepExecutor;
    }
}
