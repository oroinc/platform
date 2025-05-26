<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Exception\UnhandledErrorsException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\AssertNotHasErrors;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class AssertNotHasErrorsTest extends GetListProcessorTestCase
{
    private AssertNotHasErrors $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new AssertNotHasErrors();
    }

    public function testProcessWithoutErrorsInContext(): void
    {
        $this->processor->process($this->context);
    }

    public function testProcessWithErrorsInContext(): void
    {
        $this->expectException(UnhandledErrorsException::class);
        $this->context->addError(Error::createValidationError('some error'));
        $this->processor->process($this->context);
    }
}
