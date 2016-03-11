<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\BuildSingleItemJsonApiDocument;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class BuildSingleItemJsonApiDocumentTest extends GetProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $documentBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $documentBuilderFactory;

    /** @var BuildSingleItemJsonApiDocument */
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

        $this->processor = new BuildSingleItemJsonApiDocument($this->documentBuilderFactory);
    }

    public function testProcessContextWithoutErrorsOnEmptyResult()
    {
        $this->documentBuilder->expects($this->once())
            ->method('setDataObject')
            ->with(null);
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilderFactory->expects($this->once())
            ->method('createDocumentBuilder')
            ->willReturn($this->documentBuilder);

        $this->context->setResult(null);
        $this->processor->process($this->context);
    }

    public function testProcessContextWithoutErrorsOnNonEmptyResult()
    {
        $result   = [new \stdClass()];
        $metadata = new EntityMetadata();

        $this->documentBuilder->expects($this->once())
            ->method('setDataObject')
            ->with($result, $metadata);
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilderFactory->expects($this->once())
            ->method('createDocumentBuilder')
            ->willReturn($this->documentBuilder);

        $this->context->setResult($result);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
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
            ->method('setDataObject')
            ->willThrowException($exception);
        $this->documentBuilder->expects($this->once())
            ->method('getDocument');
        $this->documentBuilder->expects($this->once())
            ->method('setErrorObject');
        $this->documentBuilderFactory->expects($this->exactly(2))
            ->method('createDocumentBuilder')
            ->willReturn($this->documentBuilder);

        $this->context->setResult(null);
        $this->processor->process($this->context);

        $this->assertEquals(500, $this->context->getResponseStatusCode());
    }
}
