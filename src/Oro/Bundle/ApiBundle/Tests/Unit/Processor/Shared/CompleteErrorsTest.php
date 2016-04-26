<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\CompleteErrors;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class CompleteErrorsTest extends GetProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $errorCompleter;

    /** @var CompleteErrors */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->errorCompleter = $this->getMock('Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface');

        $this->processor = new CompleteErrors($this->errorCompleter);
    }

    public function testProcessWithoutErrors()
    {
        $metadata = new EntityMetadata();

        $this->errorCompleter->expects($this->never())
            ->method('complete');

        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $metadata = new EntityMetadata();

        $error = Error::createByException(new \Exception('some exception'));

        $this->errorCompleter->expects($this->once())
            ->method('complete')
            ->with($this->identicalTo($error), $this->identicalTo($metadata))
            ->willReturnCallback(
                function (Error $error) {
                    $error->setDetail($error->getInnerException()->getMessage());
                }
            );

        $this->context->addError($error);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        $this->assertEquals([$expectedError], $this->context->getErrors());
    }
}
