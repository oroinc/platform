<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Processor\DeleteList\SetDeletedCountHeader;

class SetDeletedCountHeaderTest extends DeleteListProcessorTestCase
{
    private const string REQUEST_INCLUDE_HEADER_NAME = 'X-Include';
    private const string REQUEST_DELETED_COUNT_HEADER_VALUE = 'deletedCount';
    private const string RESPONSE_DELETED_COUNT_HEADER_NAME = 'X-Include-Deleted-Count';

    private SetDeletedCountHeader $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetDeletedCountHeader();
    }

    public function testProcessWithoutRequestHeader(): void
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->getResponseHeaders()->has(self::RESPONSE_DELETED_COUNT_HEADER_NAME));
    }

    public function testProcessOnExistingHeader(): void
    {
        $testCount = 10;

        $this->context->getResponseHeaders()->set(self::RESPONSE_DELETED_COUNT_HEADER_NAME, $testCount);
        $this->processor->process($this->context);

        self::assertEquals(
            $testCount,
            $this->context->getResponseHeaders()->get(self::RESPONSE_DELETED_COUNT_HEADER_NAME)
        );
    }

    public function testProcessWhenEntitiesToBeDeletedExist(): void
    {
        $testCount = 10;
        $result = array_fill(0, $testCount, new \stdClass());

        $this->context->setResult($result);
        $this->context->getRequestHeaders()->set(
            self::REQUEST_INCLUDE_HEADER_NAME,
            [self::REQUEST_DELETED_COUNT_HEADER_VALUE]
        );
        $this->processor->process($this->context);

        self::assertEquals(
            $testCount,
            $this->context->getResponseHeaders()->get(self::RESPONSE_DELETED_COUNT_HEADER_NAME)
        );
    }

    public function testProcessWhenEntitiesToBeDeletedExistButDeletedCountWasNotRequested(): void
    {
        $testCount = 10;
        $result = array_fill(0, $testCount, new \stdClass());

        $this->context->setResult($result);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has(self::RESPONSE_DELETED_COUNT_HEADER_NAME));
    }

    public function testProcessWhenEntitiesToBeDeletedDoNotExist(): void
    {
        $this->context->getRequestHeaders()->set(
            self::REQUEST_INCLUDE_HEADER_NAME,
            [self::REQUEST_DELETED_COUNT_HEADER_VALUE]
        );
        $this->processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has(self::RESPONSE_DELETED_COUNT_HEADER_NAME));
    }
}
