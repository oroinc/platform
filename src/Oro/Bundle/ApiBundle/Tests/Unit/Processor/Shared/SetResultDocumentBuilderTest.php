<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\SetResultDocumentBuilder;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderFactory;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Symfony\Component\HttpFoundation\Response;

class SetResultDocumentBuilderTest extends GetProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DocumentBuilderFactory */
    private $documentBuilderFactory;

    /** @var SetResultDocumentBuilder */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->documentBuilderFactory = $this->createMock(DocumentBuilderFactory::class);

        $this->processor = new SetResultDocumentBuilder($this->documentBuilderFactory);
    }

    public function testProcessContextWithoutErrorsOnEmptyResult()
    {
        $result = null;

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $this->documentBuilderFactory->expects(self::once())
            ->method('createDocumentBuilder')
            ->with($this->context->getRequestType())
            ->willReturn($documentBuilder);

        $this->context->setResult($result);
        $this->processor->process($this->context);
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
    }

    public function testProcessContextWithoutErrorsOnNonEmptyResult()
    {
        $result = [new \stdClass()];

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $this->documentBuilderFactory->expects(self::once())
            ->method('createDocumentBuilder')
            ->with($this->context->getRequestType())
            ->willReturn($documentBuilder);

        $this->context->setResult($result);
        $this->processor->process($this->context);
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
    }

    public function testProcessContextWithoutErrorsOnNonEmptyResultAndErroredStatusCode()
    {
        $this->documentBuilderFactory->expects(self::never())
            ->method('createDocumentBuilder');

        $this->context->setResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->context->setResult([new \stdClass()]);
        $this->processor->process($this->context);
        self::assertNull($this->context->getResponseDocumentBuilder());
    }

    public function testProcessWithErrors()
    {
        $error = new Error();

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $this->documentBuilderFactory->expects(self::once())
            ->method('createDocumentBuilder')
            ->with($this->context->getRequestType())
            ->willReturn($documentBuilder);

        $this->context->addError($error);
        $this->context->setResult([]);
        $this->processor->process($this->context);
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
    }
}
