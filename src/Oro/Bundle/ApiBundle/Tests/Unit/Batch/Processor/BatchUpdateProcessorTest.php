<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor;

use Oro\Bundle\ApiBundle\Batch\Processor\BatchUpdateProcessor;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Component\ChainProcessor\ProcessorBagInterface;

class BatchUpdateProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateContextObject()
    {
        $processorBag = $this->createMock(ProcessorBagInterface::class);
        $processor = new BatchUpdateProcessor($processorBag, 'batch_update');

        self::assertInstanceOf(BatchUpdateContext::class, $processor->createContext());
    }
}
