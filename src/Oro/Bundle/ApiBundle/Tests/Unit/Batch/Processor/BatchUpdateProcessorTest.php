<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor;

use Oro\Bundle\ApiBundle\Batch\Processor\BatchUpdateProcessor;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use PHPUnit\Framework\TestCase;

class BatchUpdateProcessorTest extends TestCase
{
    public function testCreateContextObject(): void
    {
        $processorBag = $this->createMock(ProcessorBagInterface::class);
        $processor = new BatchUpdateProcessor($processorBag, 'batch_update');

        self::assertInstanceOf(BatchUpdateContext::class, $processor->createContext());
    }
}
