<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\BuildListResultDocument;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class BuildListResultDocumentTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ErrorCompleterRegistry */
    private $errorCompleterRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var BuildListResultDocument */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->errorCompleterRegistry = $this->createMock(ErrorCompleterRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new BuildListResultDocument(
            $this->errorCompleterRegistry,
            $this->logger
        );
    }

    public function testProcessContextWithoutErrorsOnEmptyResult()
    {
        $result = [];
        $metadata = new EntityMetadata();

        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $documentBuilder->expects(self::never())
            ->method('setMetadata');
        $documentBuilder->expects(self::once())
            ->method('setDataCollection')
            ->with($result, $this->context->getRequestType(), $metadata);
        $documentBuilder->expects(self::never())
            ->method('getDocument');

        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResult($result);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
        self::assertEquals($result, $this->context->getResult());
    }

    public function testProcessContextWithoutErrorsOnNonEmptyResult()
    {
        $result = [new \stdClass()];
        $metadata = new EntityMetadata();

        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $documentBuilder->expects(self::never())
            ->method('setMetadata');
        $documentBuilder->expects(self::once())
            ->method('setDataCollection')
            ->with($result, $this->context->getRequestType(), $metadata);
        $documentBuilder->expects(self::never())
            ->method('getDocument');

        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResult($result);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
        self::assertEquals($result, $this->context->getResult());
    }

    public function testProcessContextWithoutErrorsAndWithInfoRecords()
    {
        $result = [new \stdClass()];
        $metadata = new EntityMetadata();
        $infoRecords = ['' => ['key' => 'value']];

        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $documentBuilder->expects(self::once())
            ->method('setMetadata')
            ->with($infoRecords);
        $documentBuilder->expects(self::once())
            ->method('setDataCollection')
            ->with($result, $this->context->getRequestType(), $metadata);
        $documentBuilder->expects(self::never())
            ->method('getDocument');

        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResult($result);
        $this->context->setMetadata($metadata);
        $this->context->setInfoRecords($infoRecords);
        $this->processor->process($this->context);
        self::assertEquals($result, $this->context->getResult());
    }

    public function testProcessContextWithoutErrorsOnNonEmptyResultAndErroredStatusCode()
    {
        $result = [new \stdClass()];

        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $this->context->setResponseDocumentBuilder($this->createMock(DocumentBuilderInterface::class));
        $this->context->setResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->context->setResult($result);
        $this->processor->process($this->context);
        self::assertEquals($result, $this->context->getResult());
    }

    public function testProcessWithErrors()
    {
        $error = new Error();

        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $documentBuilder->expects(self::never())
            ->method('setMetadata');
        $documentBuilder->expects(self::never())
            ->method('setDataCollection');
        $documentBuilder->expects(self::never())
            ->method('getDocument');
        $documentBuilder->expects(self::once())
            ->method('setErrorCollection')
            ->with([$error]);

        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setClassName(User::class);
        $this->context->addError($error);
        $this->context->setResult([]);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasResult());

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithException()
    {
        $exception = new \LogicException();

        $errorCompleter = $this->createMock(ErrorCompleterInterface::class);
        $this->errorCompleterRegistry->expects(self::once())
            ->method('getErrorCompleter')
            ->with($this->context->getRequestType())
            ->willReturn($errorCompleter);

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $documentBuilder->expects(self::never())
            ->method('setMetadata');
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

        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResult(null);
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasResult());

        self::assertEquals(500, $this->context->getResponseStatusCode());
    }
}
