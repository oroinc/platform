<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateClassNameExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class ValidateClassNameExistsTest extends GetListProcessorTestCase
{
    /** @var ValidateClassNameExists */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateClassNameExists();
    }

    public function testProcess()
    {
        $this->context->setClassName('Test\Class');
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoClassName()
    {
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError(
                    'entity type constraint',
                    'The entity class must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }
}
