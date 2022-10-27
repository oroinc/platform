<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\CompleteErrors;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;

class CompleteErrorsTest extends BatchUpdateProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ErrorCompleterInterface */
    private $errorCompleter;

    /** @var CompleteErrors */
    private $processor;

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
        $this->processor->process($this->context);
    }

    public function testProcessWithErrorsInBatchUpdateContext()
    {
        $error = new Error();

        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($this->context->getRequestType()), null);

        $this->context->addError($error);
        $this->processor->process($this->context);
    }

    public function testProcessWithErrorsInBatchItem()
    {
        $error = new Error();

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $targetContext = $this->createMock(Context::class);
        $metadata = $this->createMock(EntityMetadata::class);
        $item->expects(self::any())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error]);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($targetContext);
        $targetContext->expects(self::once())
            ->method('getClassName')
            ->willReturn('Test\Entity');
        $targetContext->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadata);

        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(
                self::identicalTo($error),
                self::identicalTo($this->context->getRequestType()),
                self::identicalTo($metadata)
            );

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWithErrorsInBatchItemButEntityClassWasNotResolved()
    {
        $error = new Error();

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $targetContext = $this->createMock(Context::class);
        $item->expects(self::any())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error]);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($targetContext);
        $targetContext->expects(self::once())
            ->method('getClassName')
            ->willReturn('entity');
        $targetContext->expects(self::never())
            ->method('getMetadata');

        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($this->context->getRequestType()), null);

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWithErrorsInBatchItemButExceptionOccurredWhenGetMetadata()
    {
        $error = new Error();

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $targetContext = $this->createMock(Context::class);
        $item->expects(self::any())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error]);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($targetContext);
        $targetContext->expects(self::once())
            ->method('getClassName')
            ->willReturn('Test\Entity');
        $targetContext->expects(self::once())
            ->method('getMetadata')
            ->willThrowException(new \Exception('some error'));

        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($this->context->getRequestType()), null);

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testProcessWithErrorsInBatchItemButNoEntityClass()
    {
        $error = new Error();

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $targetContext = $this->createMock(Context::class);
        $item->expects(self::any())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('getErrors')
            ->willReturn([$error]);
        $itemContext->expects(self::once())
            ->method('getTargetContext')
            ->willReturn($targetContext);
        $targetContext->expects(self::once())
            ->method('getClassName')
            ->willReturn(null);
        $targetContext->expects(self::never())
            ->method('getMetadata');

        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($this->context->getRequestType()), null);

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
    }

    public function testRemoveDuplicates()
    {
        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = new BatchUpdateItemContext();
        $targetContext = $this->createMock(Context::class);
        $itemContext->setTargetContext($targetContext);
        $metadata = $this->createMock(EntityMetadata::class);
        $item->expects(self::any())
            ->method('getContext')
            ->willReturn($itemContext);
        $targetContext->expects(self::once())
            ->method('getClassName')
            ->willReturn('Test\Entity');
        $targetContext->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadata);

        $itemContext->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPropertyPath('path1'))
        );
        $itemContext->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPropertyPath('path2'))
        );
        $itemContext->addError(
            Error::create('title1', 'detail2')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPropertyPath('path1'))
        );
        $itemContext->addError(
            Error::create('title2', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPropertyPath('path1'))
        );
        $itemContext->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByPointer('path1'))
        );
        $itemContext->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
                ->setSource(ErrorSource::createByParameter('path1'))
        );
        $itemContext->addError(
            Error::create('title1', 'detail1')
                ->setStatusCode(400)
        );
        $itemContext->addError(
            Error::create('title1', 'detail1')
                ->setSource(ErrorSource::createByParameter('path1'))
        );
        $itemContext->addError(
            Error::create('title1', 'detail1')
        );

        $expectedErrors = $itemContext->getErrors();

        // duplicate all errors
        foreach ($expectedErrors as $error) {
            $newError = clone $error;
            if (null !== $error->getSource()) {
                $newError->setSource(clone $error->getSource());
            }
            $itemContext->addError($newError);
        }

        $this->context->setBatchItems([$item]);
        $this->processor->process($this->context);
        self::assertSame($expectedErrors, $itemContext->getErrors());
    }
}
