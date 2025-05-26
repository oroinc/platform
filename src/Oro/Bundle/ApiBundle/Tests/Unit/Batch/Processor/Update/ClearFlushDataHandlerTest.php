<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\ClearFlushDataHandler;

class ClearFlushDataHandlerTest extends BatchUpdateProcessorTestCase
{
    private ClearFlushDataHandler $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ClearFlushDataHandler();
    }

    public function testProcessWhenFlushDataHandlerIsNotSet(): void
    {
        $this->processor->process($this->context);
    }

    public function testProcess(): void
    {
        $flushDataHandler = $this->createMock(BatchFlushDataHandlerInterface::class);
        $flushDataHandler->expects(self::once())
            ->method('clear');

        $this->context->setFlushDataHandler($flushDataHandler);
        $this->processor->process($this->context);
        self::assertNull($this->context->getFlushDataHandler());
    }
}
