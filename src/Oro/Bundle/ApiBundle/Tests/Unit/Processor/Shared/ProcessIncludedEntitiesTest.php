<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Shared\ProcessIncludedEntities;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Request\ExceptionTextExtractorInterface;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

class ProcessIncludedEntitiesTest extends FormProcessorTestCase
{
    /** @var ActionProcessorBagInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $processorBag;

    /** @var ErrorCompleterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $errorCompleter;

    /** @var ExceptionTextExtractorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $exceptionTextExtractor;

    /** @var ParameterBag */
    private $sharedData;

    /** @var ProcessIncludedEntities */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sharedData = new ParameterBag();
        $this->sharedData->set('someKey', 'someSharedValue');
        $this->context->setSharedData($this->sharedData);

        $this->processorBag = $this->createMock(ActionProcessorBagInterface::class);
        $this->errorCompleter = $this->createMock(ErrorCompleterInterface::class);
        $this->exceptionTextExtractor = $this->createMock(ExceptionTextExtractorInterface::class);

        $errorCompleterRegistry = $this->createMock(ErrorCompleterRegistry::class);
        $errorCompleterRegistry->expects(self::any())
            ->method('getErrorCompleter')
            ->with($this->context->getRequestType())
            ->willReturn($this->errorCompleter);

        $this->processor = new ProcessIncludedEntities(
            $this->processorBag,
            $errorCompleterRegistry,
            $this->exceptionTextExtractor
        );
    }

    public function testProcessWhenIncludedDataIsEmpty()
    {
        $this->context->setIncludedData([]);
        $this->context->setIncludedEntities(new IncludedEntityCollection());
        $this->processor->process($this->context);
    }

    public function testProcessWhenIncludedEntityCollectionDoesNotExist()
    {
        $includedData = [
            ['data' => ['type' => 'testType', 'id' => 'testId']]
        ];

        $this->context->setIncludedData($includedData);
        $this->processor->process($this->context);
    }

    public function testProcessForNewIncludedEntityWhenCreateActionSuccess()
    {
        $includedData = [
            ['data' => ['type' => 'testType', 'id' => 'testId']]
        ];
        $includedEntity = new \stdClass();

        $includedEntities = new IncludedEntityCollection();
        $includedEntityData = new IncludedEntityData('/included/0', 0);
        $includedEntities->add($includedEntity, 'Test\Class', 'id', $includedEntityData);

        $expectedContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $expectedContext->setVersion($this->context->getVersion());
        $expectedContext->getRequestType()->set($this->context->getRequestType());
        $expectedContext->setMasterRequest(false);
        $expectedContext->setCorsRequest(false);
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setSharedData($this->sharedData);
        $expectedContext->setIncludedEntities($includedEntities);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedEntity);
        $expectedContext->skipFormValidation(true);
        $expectedContext->setLastGroup(ApiActionGroup::TRANSFORM_DATA);
        $expectedContext->setSoftErrorsHandling(true);

        $actionMetadata = new EntityMetadata('Test\Entity');

        $actionContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $actionProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiAction::CREATE)
            ->willReturn($actionProcessor);
        $actionProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($actionContext);
        $actionProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CreateContext $context) use ($expectedContext, $actionMetadata) {
                self::assertEquals($expectedContext, $context);

                $context->setMetadata($actionMetadata);
            });
        $this->errorCompleter->expects(self::never())
            ->method('fixIncludedEntityPath');
        $this->exceptionTextExtractor->expects(self::never())
            ->method('getExceptionStatusCode');

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->setMasterRequest(true);
        $this->context->setCorsRequest(true);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        self::assertFalse($actionContext->hasErrors());
        self::assertSame($actionMetadata, $includedEntityData->getMetadata());
    }

    public function testProcessForNewIncludedEntityWhenCreateActionFailed()
    {
        $includedData = [
            ['data' => ['type' => 'testType', 'id' => 'testId']]
        ];
        $includedEntity = new \stdClass();

        $includedEntities = new IncludedEntityCollection();
        $includedEntityData = new IncludedEntityData('/included/0', 0);
        $includedEntities->add($includedEntity, 'Test\Class', 'id', $includedEntityData);

        $error = Error::createValidationError('some error');

        $expectedContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $expectedContext->setVersion($this->context->getVersion());
        $expectedContext->getRequestType()->set($this->context->getRequestType());
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setSharedData($this->sharedData);
        $expectedContext->setIncludedEntities($includedEntities);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedEntity);
        $expectedContext->skipFormValidation(true);
        $expectedContext->setLastGroup(ApiActionGroup::TRANSFORM_DATA);
        $expectedContext->setSoftErrorsHandling(true);

        $actionMetadata = new EntityMetadata('Test\Entity');

        $actionContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $actionProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiAction::CREATE)
            ->willReturn($actionProcessor);
        $actionProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($actionContext);
        $actionProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CreateContext $context) use ($expectedContext, $error, $actionMetadata) {
                self::assertEquals($expectedContext, $context);

                $context->setMetadata($actionMetadata);
                $context->addError($error);
            });
        $this->errorCompleter->expects(self::once())
            ->method('fixIncludedEntityPath')
            ->with(
                $includedEntityData->getPath(),
                self::identicalTo($error),
                $expectedContext->getRequestType(),
                self::identicalTo($actionMetadata)
            );
        $this->exceptionTextExtractor->expects(self::never())
            ->method('getExceptionStatusCode');

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        self::assertEquals([$error], $actionContext->getErrors());
        self::assertNull($includedEntityData->getMetadata());
    }

    public function testProcessForNewIncludedEntityWhenCreateActionFailedWithErrorStatusCodeWithoutResponseContent()
    {
        $includedData = [
            ['data' => ['type' => 'testType', 'id' => 'testId']]
        ];
        $includedEntity = new \stdClass();

        $includedEntities = new IncludedEntityCollection();
        $includedEntityData = new IncludedEntityData('/included/0', 0);
        $includedEntities->add($includedEntity, 'Test\Class', 'id', $includedEntityData);

        $error = Error::createValidationError('action not allowed')
            ->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
        $expectedError = clone $error;
        $expectedError->setStatusCode(Response::HTTP_BAD_REQUEST);

        $expectedContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $expectedContext->setVersion($this->context->getVersion());
        $expectedContext->getRequestType()->set($this->context->getRequestType());
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setSharedData($this->sharedData);
        $expectedContext->setIncludedEntities($includedEntities);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedEntity);
        $expectedContext->skipFormValidation(true);
        $expectedContext->setLastGroup(ApiActionGroup::TRANSFORM_DATA);
        $expectedContext->setSoftErrorsHandling(true);

        $actionMetadata = new EntityMetadata('Test\Entity');

        $actionContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $actionProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiAction::CREATE)
            ->willReturn($actionProcessor);
        $actionProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($actionContext);
        $actionProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CreateContext $context) use ($expectedContext, $error, $actionMetadata) {
                self::assertEquals($expectedContext, $context);

                $context->setMetadata($actionMetadata);
                $context->addError($error);
            });
        $this->errorCompleter->expects(self::once())
            ->method('fixIncludedEntityPath')
            ->with(
                $includedEntityData->getPath(),
                self::identicalTo($error),
                $expectedContext->getRequestType(),
                self::identicalTo($actionMetadata)
            );
        $this->exceptionTextExtractor->expects(self::never())
            ->method('getExceptionStatusCode');

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        self::assertEquals([$expectedError], $actionContext->getErrors());
        self::assertNull($includedEntityData->getMetadata());
    }

    public function testProcessForExistingIncludedEntityWhenUpdateActionSuccess()
    {
        $includedData = [
            ['data' => ['type' => 'testType', 'id' => 'testId']]
        ];
        $includedEntity = new \stdClass();

        $includedEntities = new IncludedEntityCollection();
        $includedEntityData = new IncludedEntityData('/included/0', 0, true);
        $includedEntities->add($includedEntity, 'Test\Class', 'id', $includedEntityData);

        $expectedContext = new UpdateContext($this->configProvider, $this->metadataProvider);
        $expectedContext->setVersion($this->context->getVersion());
        $expectedContext->getRequestType()->set($this->context->getRequestType());
        $expectedContext->setMasterRequest(false);
        $expectedContext->setCorsRequest(false);
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setSharedData($this->sharedData);
        $expectedContext->setIncludedEntities($includedEntities);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedEntity);
        $expectedContext->skipFormValidation(true);
        $expectedContext->setLastGroup(ApiActionGroup::TRANSFORM_DATA);
        $expectedContext->setSoftErrorsHandling(true);

        $actionMetadata = new EntityMetadata('Test\Entity');

        $actionContext = new UpdateContext($this->configProvider, $this->metadataProvider);
        $actionProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiAction::UPDATE)
            ->willReturn($actionProcessor);
        $actionProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($actionContext);
        $actionProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (UpdateContext $context) use ($expectedContext, $actionMetadata) {
                self::assertEquals($expectedContext, $context);

                $context->setMetadata($actionMetadata);
            });
        $this->errorCompleter->expects(self::never())
            ->method('fixIncludedEntityPath');
        $this->exceptionTextExtractor->expects(self::never())
            ->method('getExceptionStatusCode');

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->setMasterRequest(true);
        $this->context->setCorsRequest(true);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        self::assertFalse($actionContext->hasErrors());
        self::assertSame($actionMetadata, $includedEntityData->getMetadata());
    }

    public function testProcessForExistingIncludedEntityWhenUpdateActionFailed()
    {
        $includedData = [
            ['data' => ['type' => 'testType', 'id' => 'testId']]
        ];
        $includedEntity = new \stdClass();

        $includedEntities = new IncludedEntityCollection();
        $includedEntityData = new IncludedEntityData('/included/0', 0, true);
        $includedEntities->add($includedEntity, 'Test\Class', 'id', $includedEntityData);

        $errorException = new \Exception();
        $error = Error::create('some error');
        $error->setInnerException($errorException);

        $expectedError = clone $error;
        $expectedError->setStatusCode(Response::HTTP_CONFLICT);

        $expectedContext = new UpdateContext($this->configProvider, $this->metadataProvider);
        $expectedContext->setVersion($this->context->getVersion());
        $expectedContext->getRequestType()->set($this->context->getRequestType());
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setSharedData($this->sharedData);
        $expectedContext->setIncludedEntities($includedEntities);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedEntity);
        $expectedContext->skipFormValidation(true);
        $expectedContext->setLastGroup(ApiActionGroup::TRANSFORM_DATA);
        $expectedContext->setSoftErrorsHandling(true);

        $actionMetadata = new EntityMetadata('Test\Entity');

        $actionContext = new UpdateContext($this->configProvider, $this->metadataProvider);
        $actionProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiAction::UPDATE)
            ->willReturn($actionProcessor);
        $actionProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($actionContext);
        $actionProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (UpdateContext $context) use ($expectedContext, $error, $actionMetadata) {
                self::assertEquals($expectedContext, $context);

                $context->setMetadata($actionMetadata);
                $context->addError($error);
            });
        $this->errorCompleter->expects(self::once())
            ->method('fixIncludedEntityPath')
            ->with(
                $includedEntityData->getPath(),
                self::identicalTo($error),
                $expectedContext->getRequestType(),
                self::identicalTo($actionMetadata)
            );
        $this->exceptionTextExtractor->expects(self::once())
            ->method('getExceptionStatusCode')
            ->with(self::identicalTo($errorException))
            ->willReturn(Response::HTTP_CONFLICT);

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        self::assertEquals([$expectedError], $actionContext->getErrors());
        self::assertNull($includedEntityData->getMetadata());
    }
}
