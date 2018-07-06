<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\DeleteList\RemoveDeletedCountHeader;

class RemoveDeletedCountHeaderTest extends DeleteListProcessorTestCase
{
    private const RESPONSE_DELETED_COUNT_HEADER_NAME = 'X-Include-Deleted-Count';

    /** @var RemoveDeletedCountHeader */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new RemoveDeletedCountHeader();
    }

    public function testProcessWithoutErrors()
    {
        $testCount = 123;
        $this->context->getResponseHeaders()->set(self::RESPONSE_DELETED_COUNT_HEADER_NAME, $testCount);

        $this->processor->process($this->context);

        self::assertEquals(
            $testCount,
            $this->context->getResponseHeaders()->get(self::RESPONSE_DELETED_COUNT_HEADER_NAME)
        );
    }

    public function testProcessWithErrors()
    {
        $testCount = 123;
        $this->context->getResponseHeaders()->set(self::RESPONSE_DELETED_COUNT_HEADER_NAME, $testCount);
        $this->context->addError(new Error());

        $this->processor->process($this->context);

        self::assertNull(
            $this->context->getResponseHeaders()->get(self::RESPONSE_DELETED_COUNT_HEADER_NAME)
        );
    }

    public function testProcessWithErrorsButWithoutHeader()
    {
        $this->context->addError(new Error());

        $this->processor->process($this->context);

        self::assertNull(
            $this->context->getResponseHeaders()->get(self::RESPONSE_DELETED_COUNT_HEADER_NAME)
        );
    }
}
