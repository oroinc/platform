<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Processor\Update\CheckForUnexpectedErrors;
use Oro\Bundle\ApiBundle\Model\Error;

class CheckForUnexpectedErrorsTest extends BatchUpdateProcessorTestCase
{
    private CheckForUnexpectedErrors $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new CheckForUnexpectedErrors();
    }

    public function testProcessWithoutErrorsInContext(): void
    {
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasUnexpectedErrors());
    }

    public function testProcessWithErrorsInContext(): void
    {
        $this->context->addError(Error::create('some error'));
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasUnexpectedErrors());
    }
}
