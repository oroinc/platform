<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Exception\InvalidHeaderValueException;
use Oro\Bundle\ApiBundle\Processor\UpdateList\CheckProcessingMode;

class CheckProcessingModeTest extends UpdateListProcessorTestCase
{
    private CheckProcessingMode $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new CheckProcessingMode();
    }

    public function testProcessWhenSynchronousModeAlreadySet(): void
    {
        $this->context->setSynchronousMode('sync');
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasSynchronousMode());
        self::assertTrue($this->context->isSynchronousMode());
    }

    public function testProcessWhenNoModeHeader(): void
    {
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasSynchronousMode());
        self::assertFalse($this->context->isSynchronousMode());
    }

    public function testProcessWithModeHeaderSync(): void
    {
        $this->context->getRequestHeaders()->set('X-Mode', 'sync');
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasSynchronousMode());
        self::assertTrue($this->context->isSynchronousMode());
    }

    public function testProcessWithModeHeaderAsync(): void
    {
        $this->context->getRequestHeaders()->set('X-Mode', 'async');
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasSynchronousMode());
        self::assertFalse($this->context->isSynchronousMode());
    }

    public function testProcessWithModeHeaderInvalidValue(): void
    {
        $this->expectException(InvalidHeaderValueException::class);
        $this->expectExceptionMessage('The accepted values for the "X-Mode" are "sync" or "async".');

        $this->context->getRequestHeaders()->set('X-Mode', 'other');
        $this->processor->process($this->context);
    }
}
