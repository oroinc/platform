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
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

class ProcessIncludedEntitiesTest extends FormProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionProcessorBagInterface */
    private $processorBag;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ErrorCompleterInterface */
    private $errorCompleter;

    /** @var ProcessIncludedEntities */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processorBag = $this->createMock(ActionProcessorBagInterface::class);
        $this->errorCompleter = $this->createMock(ErrorCompleterInterface::class);

        $errorCompleterRegistry = $this->createMock(ErrorCompleterRegistry::class);
        $errorCompleterRegistry->expects(self::any())
            ->method('getErrorCompleter')
            ->with($this->context->getRequestType())
            ->willReturn($this->errorCompleter);

        $this->processor = new ProcessIncludedEntities(
            $this->processorBag,
            $errorCompleterRegistry
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
        $expectedContext->setIncludedEntities($includedEntities);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedEntity);
        $expectedContext->skipFormValidation(true);
        $expectedContext->setLastGroup('transform_data');
        $expectedContext->setSoftErrorsHandling(true);

        $actionMetadata = new EntityMetadata();

        $actionContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $actionProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiActions::CREATE)
            ->willReturn($actionProcessor);
        $actionProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($actionContext);
        $actionProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CreateContext $context) use ($expectedContext, $actionMetadata) {
                    self::assertEquals($expectedContext, $context);

                    $context->setMetadata($actionMetadata);
                }
            );
        $this->errorCompleter->expects(self::never())
            ->method('complete');

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
        $expectedContext->setIncludedEntities($includedEntities);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedEntity);
        $expectedContext->skipFormValidation(true);
        $expectedContext->setLastGroup('transform_data');
        $expectedContext->setSoftErrorsHandling(true);

        $actionMetadata = new EntityMetadata();

        $actionContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $actionProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiActions::CREATE)
            ->willReturn($actionProcessor);
        $actionProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($actionContext);
        $actionProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CreateContext $context) use ($expectedContext, $error, $actionMetadata) {
                    self::assertEquals($expectedContext, $context);

                    $context->setMetadata($actionMetadata);
                    $context->addError($error);
                }
            );
        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(
                self::identicalTo($error),
                $expectedContext->getRequestType(),
                self::identicalTo($actionMetadata)
            );

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        self::assertEquals([$error], $actionContext->getErrors());
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
        $expectedContext->setIncludedEntities($includedEntities);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedEntity);
        $expectedContext->skipFormValidation(true);
        $expectedContext->setLastGroup('transform_data');
        $expectedContext->setSoftErrorsHandling(true);

        $actionMetadata = new EntityMetadata();

        $actionContext = new UpdateContext($this->configProvider, $this->metadataProvider);
        $actionProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiActions::UPDATE)
            ->willReturn($actionProcessor);
        $actionProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($actionContext);
        $actionProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (UpdateContext $context) use ($expectedContext, $actionMetadata) {
                    self::assertEquals($expectedContext, $context);

                    $context->setMetadata($actionMetadata);
                }
            );
        $this->errorCompleter->expects(self::never())
            ->method('complete');

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

        $error = Error::createValidationError('some error');

        $expectedContext = new UpdateContext($this->configProvider, $this->metadataProvider);
        $expectedContext->setVersion($this->context->getVersion());
        $expectedContext->getRequestType()->set($this->context->getRequestType());
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setIncludedEntities($includedEntities);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedEntity);
        $expectedContext->skipFormValidation(true);
        $expectedContext->setLastGroup('transform_data');
        $expectedContext->setSoftErrorsHandling(true);

        $actionMetadata = new EntityMetadata();

        $actionContext = new UpdateContext($this->configProvider, $this->metadataProvider);
        $actionProcessor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiActions::UPDATE)
            ->willReturn($actionProcessor);
        $actionProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($actionContext);
        $actionProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (UpdateContext $context) use ($expectedContext, $error, $actionMetadata) {
                    self::assertEquals($expectedContext, $context);

                    $context->setMetadata($actionMetadata);
                    $context->addError($error);
                }
            );
        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(
                self::identicalTo($error),
                $expectedContext->getRequestType(),
                self::identicalTo($actionMetadata)
            );

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        self::assertEquals([$error], $actionContext->getErrors());
        self::assertNull($includedEntityData->getMetadata());
    }
}
