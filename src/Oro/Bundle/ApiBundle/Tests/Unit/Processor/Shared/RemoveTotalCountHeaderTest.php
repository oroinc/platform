<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\RemoveTotalCountHeader;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class RemoveTotalCountHeaderTest extends GetListProcessorTestCase
{
    private const RESPONSE_TOTAL_COUNT_HEADER_NAME = 'X-Include-Total-Count';

    /** @var RemoveTotalCountHeader */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new RemoveTotalCountHeader();
    }

    public function testProcessWithoutErrors()
    {
        $testCount = 123;
        $this->context->getResponseHeaders()->set(self::RESPONSE_TOTAL_COUNT_HEADER_NAME, $testCount);

        $this->processor->process($this->context);

        self::assertEquals(
            $testCount,
            $this->context->getResponseHeaders()->get(self::RESPONSE_TOTAL_COUNT_HEADER_NAME)
        );
    }

    public function testProcessWithErrors()
    {
        $testCount = 123;
        $this->context->getResponseHeaders()->set(self::RESPONSE_TOTAL_COUNT_HEADER_NAME, $testCount);
        $this->context->addError(new Error());

        $this->processor->process($this->context);

        self::assertNull(
            $this->context->getResponseHeaders()->get(self::RESPONSE_TOTAL_COUNT_HEADER_NAME)
        );
    }

    public function testProcessWithErrorsButWithoutHeader()
    {
        $this->context->addError(new Error());

        $this->processor->process($this->context);

        self::assertNull(
            $this->context->getResponseHeaders()->get(self::RESPONSE_TOTAL_COUNT_HEADER_NAME)
        );
    }
}
