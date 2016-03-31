<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Processor\DeleteList\SetDeletedCountHeader;

class SetDeletedCountHeaderTest extends DeleteListProcessorTestCase
{
    const REQUEST_INCLUDE_HEADER_NAME        = 'X-Include';
    const REQUEST_DELETED_COUNT_HEADER_VALUE = 'deletedCount';
    const RESPONSE_DELETED_COUNT_HEADER_NAME = 'X-Include-Deleted-Count';

    /** @var SetDeletedCountHeader */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new SetDeletedCountHeader();
    }

    public function testProcessWithoutRequestHeader()
    {
        $this->processor->process($this->context);
        $this->assertFalse($this->context->getResponseHeaders()->has(self::RESPONSE_DELETED_COUNT_HEADER_NAME));
    }

    public function testProcessOnExistingHeader()
    {
        $testCount = 10;

        $this->context->getResponseHeaders()->set(self::RESPONSE_DELETED_COUNT_HEADER_NAME, $testCount);
        $this->processor->process($this->context);

        $this->assertEquals(
            $testCount,
            $this->context->getResponseHeaders()->get(self::RESPONSE_DELETED_COUNT_HEADER_NAME)
        );
    }

    public function testProcessWhenEntitiesToBeDeletedExist()
    {
        $testCount = 10;
        $result = array_fill(0, $testCount, new \stdClass());

        $this->context->setResult($result);
        $this->context->getRequestHeaders()->set(
            self::REQUEST_INCLUDE_HEADER_NAME,
            [self::REQUEST_DELETED_COUNT_HEADER_VALUE]
        );
        $this->processor->process($this->context);

        $this->assertEquals(
            $testCount,
            $this->context->getResponseHeaders()->get(self::RESPONSE_DELETED_COUNT_HEADER_NAME)
        );
    }

    public function testProcessWhenEntitiesToBeDeletedExistButDeletedCountWasNotRequested()
    {
        $testCount = 10;
        $result = array_fill(0, $testCount, new \stdClass());

        $this->context->setResult($result);
        $this->processor->process($this->context);

        $this->assertFalse($this->context->getResponseHeaders()->has(self::RESPONSE_DELETED_COUNT_HEADER_NAME));
    }

    public function testProcessWhenEntitiesToBeDeletedDoNotExist()
    {
        $this->context->getRequestHeaders()->set(
            self::REQUEST_INCLUDE_HEADER_NAME,
            [self::REQUEST_DELETED_COUNT_HEADER_VALUE]
        );
        $this->processor->process($this->context);

        $this->assertFalse($this->context->getResponseHeaders()->has(self::RESPONSE_DELETED_COUNT_HEADER_NAME));
    }
}
