<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeSubresource;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource\CompleteErrors;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeSubresourceProcessorTestCase;

class CompleteErrorsTest extends ChangeSubresourceProcessorTestCase
{
    /** @var ErrorCompleterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $errorCompleter;

    /** @var CompleteErrors */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->errorCompleter = $this->createMock(ErrorCompleterInterface::class);

        $errorCompleterRegistry = $this->createMock(ErrorCompleterRegistry::class);
        $errorCompleterRegistry->expects(self::any())
            ->method('getErrorCompleter')
            ->with($this->context->getRequestType())
            ->willReturn($this->errorCompleter);

        $this->processor = new CompleteErrors($errorCompleterRegistry);
    }

    public function testProcessWithoutErrors()
    {
        $metadata = new EntityMetadata('Test\Entity');

        $this->errorCompleter->expects(self::never())
            ->method('complete');

        $this->context->setRequestMetadata($metadata);
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $metadata = new EntityMetadata('Test\Entity');

        $error = Error::createByException(new \Exception('some exception'));

        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(
                self::identicalTo($error),
                self::identicalTo($this->context->getRequestType()),
                self::identicalTo($metadata)
            )
            ->willReturnCallback(function (Error $error) {
                $error->setDetail($error->getInnerException()->getMessage());
            });

        $this->context->addError($error);
        $this->context->setParentClassName('Test\ParentEntity');
        $this->context->setRequestClassName('Test\Entity');
        $this->context->setRequestDocumentationAction(null);
        $this->context->setRequestMetadata($metadata);
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        self::assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testProcessWhenNoParentEntityClass()
    {
        $error = Error::createByException(new \Exception('some exception'));

        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($this->context->getRequestType()), null)
            ->willReturnCallback(function (Error $error) {
                $error->setDetail($error->getInnerException()->getMessage());
            });

        $this->context->addError($error);
        $this->context->setParentClassName(null);
        $this->context->setRequestClassName('Test\Entity');
        $this->context->setRequestDocumentationAction(null);
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        self::assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testProcessWhenParentEntityTypeWasNotConvertedToEntityClass()
    {
        $error = Error::createByException(new \Exception('some exception'));

        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($this->context->getRequestType()), null)
            ->willReturnCallback(function (Error $error) {
                $error->setDetail($error->getInnerException()->getMessage());
            });

        $this->context->addError($error);
        $this->context->setParentClassName('testParent');
        $this->context->setRequestClassName('Test\Entity');
        $this->context->setRequestDocumentationAction(null);
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        self::assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testProcessWhenNoEntityClass()
    {
        $error = Error::createByException(new \Exception('some exception'));

        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($this->context->getRequestType()), null)
            ->willReturnCallback(function (Error $error) {
                $error->setDetail($error->getInnerException()->getMessage());
            });

        $this->context->addError($error);
        $this->context->setParentClassName('Test\ParentEntity');
        $this->context->setRequestClassName(null);
        $this->context->setRequestDocumentationAction(null);
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        self::assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testProcessWhenEntityTypeWasNotConvertedToEntityClass()
    {
        $error = Error::createByException(new \Exception('some exception'));

        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($this->context->getRequestType()), null)
            ->willReturnCallback(function (Error $error) {
                $error->setDetail($error->getInnerException()->getMessage());
            });

        $this->context->addError($error);
        $this->context->setParentClassName('Test\ParentEntity');
        $this->context->setRequestClassName('test');
        $this->context->setRequestDocumentationAction(null);
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        self::assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testProcessWhenLoadConfigFailed()
    {
        $error = Error::createByException(new \Exception('some exception'));

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willThrowException(new \Exception('load config exception'));
        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($this->context->getRequestType()), null)
            ->willReturnCallback(function (Error $error) {
                $error->setDetail($error->getInnerException()->getMessage());
            });

        $this->context->addError($error);
        $this->context->setParentClassName('Test\ParentEntity');
        $this->context->setRequestClassName('Test\Entity');
        $this->context->setRequestDocumentationAction(null);
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        self::assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testProcessWhenLoadMetadataFailed()
    {
        $error = Error::createByException(new \Exception('some exception'));

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->willThrowException(new \Exception('load metadata exception'));
        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($this->context->getRequestType()), null)
            ->willReturnCallback(function (Error $error) {
                $error->setDetail($error->getInnerException()->getMessage());
            });

        $this->context->addError($error);
        $this->context->setParentClassName('Test\ParentEntity');
        $this->context->setRequestClassName('Test\Entity');
        $this->context->setRequestDocumentationAction(null);
        $this->context->setRequestConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        self::assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testRemoveDuplicates()
    {
        $this->context->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPropertyPath('path1'))
        );
        $this->context->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPropertyPath('path2'))
        );
        $this->context->addError(
            Error::create('title1', 'detail2')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPropertyPath('path1'))
        );
        $this->context->addError(
            Error::create('title2', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPropertyPath('path1'))
        );
        $this->context->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPointer('path1'))
        );
        $this->context->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByParameter('path1'))
        );
        $this->context->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
        );
        $this->context->addError(
            Error::create('title1', 'detail1')
                ->setSource(ErrorSource::createByParameter('path1'))
        );
        $this->context->addError(
            Error::create('title1', 'detail1')
        );

        $expectedErrors = $this->context->getErrors();

        // duplicate all errors
        foreach ($expectedErrors as $error) {
            $newError = clone $error;
            if (null !== $error->getSource()) {
                $newError->setSource(clone $error->getSource());
            }
            $this->context->addError($newError);
        }

        $this->context->setParentClassName('Test\ParentEntity');
        $this->context->setRequestClassName('Test\Entity');
        $this->context->setRequestDocumentationAction(null);
        $this->processor->process($this->context);
        self::assertSame($expectedErrors, $this->context->getErrors());
    }
}
