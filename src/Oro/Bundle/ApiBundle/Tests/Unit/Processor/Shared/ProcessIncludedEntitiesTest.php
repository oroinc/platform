<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
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
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class ProcessIncludedEntitiesTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $processorBag;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $errorCompleter;

    /** @var ProcessIncludedEntities */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processorBag = $this->getMock(ActionProcessorBagInterface::class);
        $this->errorCompleter = $this->getMock(ErrorCompleterInterface::class);

        $this->processor = new ProcessIncludedEntitiesStub(
            $this->processorBag,
            $this->errorCompleter
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
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setIncludedEntities($includedEntities);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedEntity);
        $expectedContext->setLastGroup('transform_data');
        $expectedContext->setSoftErrorsHandling(true);

        $actionMetadata = new EntityMetadata();

        $actionContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $actionProcessor = $this->getMock(ActionProcessorInterface::class);
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
                    $this->assertEquals($expectedContext, $context);

                    $context->setMetadata($actionMetadata);
                }
            );
        $this->errorCompleter->expects(self::never())
            ->method('complete');

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        $this->assertFalse($actionContext->hasErrors());
        $this->assertSame($actionMetadata, $includedEntityData->getMetadata());
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
        $expectedContext->setLastGroup('transform_data');
        $expectedContext->setSoftErrorsHandling(true);

        $actionMetadata = new EntityMetadata();

        $actionContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $actionProcessor = $this->getMock(ActionProcessorInterface::class);
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
                    $this->assertEquals($expectedContext, $context);

                    $context->setMetadata($actionMetadata);
                    $context->addError($error);
                }
            );
        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($actionMetadata));

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        $this->assertEquals([$error], $actionContext->getErrors());
        $this->assertNull($includedEntityData->getMetadata());
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
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setIncludedEntities($includedEntities);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedEntity);
        $expectedContext->setLastGroup('transform_data');
        $expectedContext->setSoftErrorsHandling(true);

        $actionMetadata = new EntityMetadata();

        $actionContext = new UpdateContext($this->configProvider, $this->metadataProvider);
        $actionProcessor = $this->getMock(ActionProcessorInterface::class);
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
                    $this->assertEquals($expectedContext, $context);

                    $context->setMetadata($actionMetadata);
                }
            );
        $this->errorCompleter->expects(self::never())
            ->method('complete');

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        $this->assertFalse($actionContext->hasErrors());
        $this->assertSame($actionMetadata, $includedEntityData->getMetadata());
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
        $expectedContext->setLastGroup('transform_data');
        $expectedContext->setSoftErrorsHandling(true);

        $actionMetadata = new EntityMetadata();

        $actionContext = new UpdateContext($this->configProvider, $this->metadataProvider);
        $actionProcessor = $this->getMock(ActionProcessorInterface::class);
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
                    $this->assertEquals($expectedContext, $context);

                    $context->setMetadata($actionMetadata);
                    $context->addError($error);
                }
            );
        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($actionMetadata));

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        $this->assertEquals([$error], $actionContext->getErrors());
        $this->assertNull($includedEntityData->getMetadata());
    }
}
