<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Delete\JsonApi\BuildJsonApiDocument;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class BuildJsonApiDocumentTest extends GetProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $documentBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $documentBuilderFactory;

    /** @var BuildJsonApiDocument */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->documentBuilder        = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->documentBuilderFactory = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilderFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new BuildJsonApiDocument($this->documentBuilderFactory);
    }

    public function testProcessWithErrors()
    {
        $error = new Error();

        $this->documentBuilder->expects($this->never())
            ->method('setDataObject');
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilder->expects($this->once())
            ->method('setErrorCollection')
            ->with([$error]);
        $this->documentBuilderFactory->expects($this->once())
            ->method('createDocumentBuilder')
            ->willReturn($this->documentBuilder);

        $this->context->addError($error);
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }

    public function testProcessWithException()
    {
        $exception = new \LogicException();

        $this->documentBuilder->expects($this->once())
            ->method('setErrorCollection')
            ->willThrowException($exception);
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilder->expects($this->once())
            ->method('setErrorObject');
        $this->documentBuilderFactory->expects($this->exactly(2))
            ->method('createDocumentBuilder')
            ->willReturn($this->documentBuilder);

        $this->context->addError(new Error());
        $this->context->setResult(null);
        $this->processor->process($this->context);

        $this->assertEquals(500, $this->context->getResponseStatusCode());
    }
}
