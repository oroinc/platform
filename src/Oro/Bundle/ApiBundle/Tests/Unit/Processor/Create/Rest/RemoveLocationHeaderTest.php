<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\Rest;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Create\Rest\RemoveLocationHeader;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class RemoveLocationHeaderTest extends FormProcessorTestCase
{
    private const RESPONSE_LOCATION_HEADER_NAME = 'Location';

    /** @var RemoveLocationHeader */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new RemoveLocationHeader();
    }

    public function testProcessWithoutErrors()
    {
        $testCount = 123;
        $this->context->getResponseHeaders()->set(self::RESPONSE_LOCATION_HEADER_NAME, $testCount);

        $this->processor->process($this->context);

        self::assertEquals(
            $testCount,
            $this->context->getResponseHeaders()->get(self::RESPONSE_LOCATION_HEADER_NAME)
        );
    }

    public function testProcessWithErrors()
    {
        $testCount = 123;
        $this->context->getResponseHeaders()->set(self::RESPONSE_LOCATION_HEADER_NAME, $testCount);
        $this->context->addError(new Error());

        $this->processor->process($this->context);

        self::assertNull(
            $this->context->getResponseHeaders()->get(self::RESPONSE_LOCATION_HEADER_NAME)
        );
    }

    public function testProcessWithErrorsButWithoutHeader()
    {
        $this->context->addError(new Error());

        $this->processor->process($this->context);

        self::assertNull(
            $this->context->getResponseHeaders()->get(self::RESPONSE_LOCATION_HEADER_NAME)
        );
    }
}
