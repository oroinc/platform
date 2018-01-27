<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\ProcessErrors;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class ProcessErrorsTest extends GetListProcessorTestCase
{
    /** @var ProcessErrors */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ProcessErrors();
    }

    public function testProcessWithoutErrorsInContext()
    {
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\UnhandledErrorsException
     */
    public function testProcessWithErrorsInContext()
    {
        $this->context->addError(Error::createValidationError('some error'));
        $this->processor->process($this->context);
    }
}
