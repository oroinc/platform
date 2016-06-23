<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateRequestTypeAndVersion;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class ValidateRequestTypeAndVersionTest extends GetListProcessorTestCase
{
    /** @var ValidateRequestTypeAndVersion */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateRequestTypeAndVersion();
    }

    public function testProcess()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoRequestType()
    {
        $this->context->getRequestType()->clear();
        $this->processor->process($this->context);

        $this->assertEquals(
            [
                Error::createValidationError(
                    'request type constraint',
                    'The type of a request must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenVersionIsNotSet()
    {
        $this->context->setVersion(null);
        $this->processor->process($this->context);
        $this->assertEquals('latest', $this->context->getVersion());
    }

    public function testProcessWhenVersionIsSet()
    {
        $version = '2.1';
        $this->context->setVersion($version);
        $this->processor->process($this->context);
        $this->assertSame($version, $this->context->getVersion());
    }

    public function testProcessWhenVersionHasMeaninglessPrefix()
    {
        $this->context->setVersion('v1.2');
        $this->processor->process($this->context);
        $this->assertEquals('1.2', $this->context->getVersion());
    }
}
