<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Processor\Update\SkipFlushData;

class SkipFlushDataTest extends BatchUpdateProcessorTestCase
{
    private SkipFlushData $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SkipFlushData();
    }

    public function testProcess(): void
    {
        $this->processor->process($this->context);
        self::assertTrue($this->context->isSkipFlushData());
    }
}
