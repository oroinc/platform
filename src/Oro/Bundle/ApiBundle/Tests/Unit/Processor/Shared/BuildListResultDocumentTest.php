<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildListResultDocument;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderFactory;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class BuildListResultDocumentTest extends GetListProcessorTestCase
{
    /** @var BuildListResultDocument */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $documentBuilderFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $errorCompleterRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    protected function setUp()
    {
        parent::setUp();

        $this->documentBuilderFactory = $this->createMock(DocumentBuilderFactory::class);
        $this->errorCompleterRegistry = $this->createMock(ErrorCompleterRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new BuildListResultDocument(
            $this->documentBuilderFactory,
            $this->errorCompleterRegistry,
            $this->logger
        );
    }

    public function testProcessContextWithoutErrorsOnEmptyResult()
    {
        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $this->documentBuilderFactory->expects(self::once())
            ->method('createDocumentBuilder')
            ->with($this->context->getRequestType())
            ->willReturn($documentBuilder);
        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $documentBuilder->expects(self::once())
            ->method('setDataCollection')
            ->with(null, $this->context->getRequestType());
        $documentBuilder->expects(self::never())
            ->method('getDocument');

        $this->context->setResult(null);
        $this->processor->process($this->context);
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
        self::assertFalse($this->context->hasResult());
    }

    public function testProcessContextWithoutErrorsOnNonEmptyResult()
    {
        $result   = [new \stdClass()];
        $metadata = new EntityMetadata();

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $this->documentBuilderFactory->expects(self::once())
            ->method('createDocumentBuilder')
            ->with($this->context->getRequestType())
            ->willReturn($documentBuilder);
        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $documentBuilder->expects(self::once())
            ->method('setDataCollection')
            ->with($result, $this->context->getRequestType(), $metadata);
        $documentBuilder->expects(self::never())
            ->method('getDocument');

        $this->context->setResult($result);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
        self::assertFalse($this->context->hasResult());
    }

    public function testProcessContextWithoutErrorsOnNonEmptyResultAndErroredStatusCode()
    {
        $this->documentBuilderFactory->expects(self::never())
            ->method('createDocumentBuilder');
        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $this->context->setResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->context->setResult([new \stdClass()]);
        $this->processor->process($this->context);
        self::assertNull($this->context->getResponseDocumentBuilder());
        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWithErrors()
    {
        $error = new Error();

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $this->documentBuilderFactory->expects(self::once())
            ->method('createDocumentBuilder')
            ->with($this->context->getRequestType())
            ->willReturn($documentBuilder);
        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $documentBuilder->expects(self::never())
            ->method('setDataCollection');
        $documentBuilder->expects(self::never())
            ->method('getDocument');
        $documentBuilder->expects(self::once())
            ->method('setErrorCollection')
            ->with([$error]);

        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
        $this->context->addError($error);
        $this->context->setResult([]);
        $this->processor->process($this->context);
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
        self::assertFalse($this->context->hasResult());

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithException()
    {
        $exception = new \LogicException();

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $this->documentBuilderFactory->expects(self::once())
            ->method('createDocumentBuilder')
            ->with($this->context->getRequestType())
            ->willReturn($documentBuilder);
        $errorCompleter = $this->createMock(ErrorCompleterInterface::class);
        $this->errorCompleterRegistry->expects(self::once())
            ->method('getErrorCompleter')
            ->with($this->context->getRequestType())
            ->willReturn($errorCompleter);

        $documentBuilder->expects(self::once())
            ->method('setDataCollection')
            ->willThrowException($exception);
        $documentBuilder->expects(self::never())
            ->method('getDocument');
        $documentBuilder->expects(self::once())
            ->method('setErrorObject');

        $errorCompleter->expects(self::once())
            ->method('complete');

        $this->logger->expects(self::once())
            ->method('error');

        $this->context->setResult(null);
        $this->processor->process($this->context);
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
        self::assertFalse($this->context->hasResult());

        self::assertEquals(500, $this->context->getResponseStatusCode());
    }
}
