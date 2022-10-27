<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UnhandledError;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\UnhandledError\BuildResultDocument;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Psr\Log\LoggerInterface;

class BuildResultDocumentTest extends UnhandledErrorProcessorTestCase
{
    /** @var ErrorCompleterRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $errorCompleterRegistry;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var BuildResultDocument */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->errorCompleterRegistry = $this->createMock(ErrorCompleterRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new BuildResultDocument(
            $this->errorCompleterRegistry,
            $this->logger
        );
    }

    public function testProcessWithErrors(): void
    {
        $error = new Error();

        $this->errorCompleterRegistry->expects(self::never())
            ->method('getErrorCompleter');

        $this->logger->expects(self::never())
            ->method(self::anything());

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
    }

    public function testProcessWithoutErrors(): void
    {
        $exception = new RuntimeException('Invalid error handling: the context must contain an error object.');
        $error = Error::createByException($exception);

        $errorCompleter = $this->createMock(ErrorCompleterInterface::class);
        $this->errorCompleterRegistry->expects(self::once())
            ->method('getErrorCompleter')
            ->with($this->context->getRequestType())
            ->willReturn($errorCompleter);

        $errorCompleter->expects(self::once())
            ->method('complete')
            ->with($error, self::identicalTo($this->context->getRequestType()));

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $documentBuilder->expects(self::once())
            ->method('clear');
        $documentBuilder->expects(self::once())
            ->method('setErrorObject')
            ->with($error);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Building of the result document failed.', [
                'exception' => $exception,
                'action'    => 'unhandled_error'
            ]);

        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->context->setClassName(User::class);
        $this->context->setResult([]);
        $this->processor->process($this->context);
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());
        self::assertFalse($this->context->hasResult());
        self::assertFalse($this->context->hasErrors());

        self::assertEquals(500, $this->context->getResponseStatusCode());
    }
}
