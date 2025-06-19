<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\UpdateList\BuildResultDocument;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class BuildResultDocumentTest extends UpdateListProcessorTestCase
{
    private ErrorCompleterRegistry&MockObject $errorCompleterRegistry;
    private LoggerInterface&MockObject $logger;
    private BuildResultDocument $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->errorCompleterRegistry = $this->createMock(ErrorCompleterRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new BuildResultDocument(
            $this->errorCompleterRegistry,
            $this->logger
        );

        $this->context->setAction(ApiAction::UPDATE_LIST);
    }

    public function testProcessForSynchronousMode(): void
    {
        $result = [new \stdClass()];
        $metadata = new EntityMetadata('Test\Entity');

        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $documentBuilder->expects(self::never())
            ->method('setMetadata');
        $documentBuilder->expects(self::never())
            ->method('setDataObject');
        $documentBuilder->expects(self::never())
            ->method('getDocument');

        $this->context->setSynchronousMode(true);
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResult($result);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
        self::assertEquals($result, $this->context->getResult());
        self::assertNull($this->context->getResponseDocumentBuilder());
    }

    public function testProcessForSynchronousModeAndWithAsyncOperationResult(): void
    {
        $result = [new \stdClass()];
        $metadata = new EntityMetadata(AsyncOperation::class);

        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $documentBuilder->expects(self::never())
            ->method('setMetadata');
        $documentBuilder->expects(self::once())
            ->method('setDataObject')
            ->with($result, $this->context->getRequestType(), $metadata);
        $documentBuilder->expects(self::never())
            ->method('getDocument');

        $this->context->setSynchronousMode(true);
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResult($result);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
        self::assertEquals($result, $this->context->getResult());
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
    }

    public function testProcessForAsyncOperationAndContextWithoutErrorsAndEmptyResult(): void
    {
        $result = [];
        $metadata = new EntityMetadata(AsyncOperation::class);

        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $documentBuilder->expects(self::never())
            ->method('setMetadata');
        $documentBuilder->expects(self::once())
            ->method('setDataObject')
            ->with($result, $this->context->getRequestType(), $metadata);
        $documentBuilder->expects(self::never())
            ->method('getDocument');

        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResult($result);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
        self::assertEquals($result, $this->context->getResult());
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
    }

    public function testProcessForAsyncOperationAndContextWithoutErrorsAndNonEmptyResult(): void
    {
        $result = [new \stdClass()];
        $metadata = new EntityMetadata(AsyncOperation::class);

        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $documentBuilder->expects(self::never())
            ->method('setMetadata');
        $documentBuilder->expects(self::once())
            ->method('setDataObject')
            ->with($result, $this->context->getRequestType(), $metadata);
        $documentBuilder->expects(self::never())
            ->method('getDocument');

        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResult($result);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
        self::assertEquals($result, $this->context->getResult());
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
    }

    public function testProcessForAsyncOperationAndContextWithoutErrorsAndWithInfoRecords(): void
    {
        $result = [new \stdClass()];
        $metadata = new EntityMetadata(AsyncOperation::class);
        $infoRecords = ['' => ['key' => 'value']];

        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $documentBuilder->expects(self::once())
            ->method('setMetadata')
            ->with($infoRecords);
        $documentBuilder->expects(self::once())
            ->method('setDataObject')
            ->with($result, $this->context->getRequestType(), $metadata);
        $documentBuilder->expects(self::never())
            ->method('getDocument');

        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResult($result);
        $this->context->setMetadata($metadata);
        $this->context->setInfoRecords($infoRecords);
        $this->processor->process($this->context);
        self::assertEquals($result, $this->context->getResult());
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
    }

    public function testProcessForAsyncOperationAndContextWithoutErrorsAndNonEmptyResultAndErroredStatusCode(): void
    {
        $result = [new \stdClass()];

        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);

        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->context->setResult($result);
        $this->processor->process($this->context);
        self::assertEquals($result, $this->context->getResult());
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
    }

    public function testProcessForAsyncOperationAndWithErrors(): void
    {
        $error = new Error();

        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $documentBuilder->expects(self::never())
            ->method('setMetadata');
        $documentBuilder->expects(self::never())
            ->method('setDataObject');
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
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
    }

    public function testProcessForAsyncOperationAndWithException(): void
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
            ->method('setDataObject')
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
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
    }
}
