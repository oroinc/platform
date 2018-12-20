<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeIncludeHeader;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class NormalizeIncludeHeaderTest extends GetListProcessorTestCase
{
    /** @var NormalizeIncludeHeader */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new NormalizeIncludeHeader();
    }

    public function testProcessWhenNoIncludeHeader()
    {
        $this->processor->process($this->context);

        self::assertFalse($this->context->getRequestHeaders()->has(Context::INCLUDE_HEADER));
    }

    public function testProcessWhenIncludeHeaderAlreadyNormalized()
    {
        $this->context->getRequestHeaders()->set(Context::INCLUDE_HEADER, ['val1', 'val2']);

        $this->processor->process($this->context);

        self::assertEquals(
            ['val1', 'val2'],
            $this->context->getRequestHeaders()->get(Context::INCLUDE_HEADER)
        );
    }

    public function testProcess()
    {
        $this->context->getRequestHeaders()->set(Context::INCLUDE_HEADER, 'val1; val2');

        $this->processor->process($this->context);

        self::assertEquals(
            ['val1', 'val2'],
            $this->context->getRequestHeaders()->get(Context::INCLUDE_HEADER)
        );
    }
}
