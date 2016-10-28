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

    public function testProcessForNewIncludedEntity()
    {
        $includedData = [
            ['data' => ['type' => 'testType', 'id' => 'testId']]
        ];
        $includedEntity = new \stdClass();

        $includedEntities = new IncludedEntityCollection();
        $includedEntities->add(
            $includedEntity,
            'Test\Class',
            'id',
            new IncludedEntityData('/included/0', 0)
        );

        $metadata = new EntityMetadata();
        $error = Error::createValidationError('some error');

        $expectedContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $expectedContext->setVersion($this->context->getVersion());
        $expectedContext->getRequestType()->set($this->context->getRequestType());
        $expectedContext->setMetadata($metadata);
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setIncludedEntities($includedEntities);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedEntity);
        $expectedContext->setLastGroup('transform_data');
        $expectedContext->setSoftErrorsHandling(true);

        $actionContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $actionContext->setMetadata($metadata);
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
                function (CreateContext $context) use ($expectedContext, $error) {
                    $this->assertEquals($expectedContext, $context);

                    $context->addError($error);
                }
            );
        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($metadata));

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        $this->assertEquals([$error], $actionContext->getErrors());
    }

    public function testProcessForExistingIncludedEntity()
    {
        $includedData = [
            ['data' => ['type' => 'testType', 'id' => 'testId']]
        ];
        $includedEntity = new \stdClass();

        $includedEntities = new IncludedEntityCollection();
        $includedEntities->add(
            $includedEntity,
            'Test\Class',
            'id',
            new IncludedEntityData('/included/0', 0, true)
        );

        $metadata = new EntityMetadata();
        $error = Error::createValidationError('some error');

        $expectedContext = new UpdateContext($this->configProvider, $this->metadataProvider);
        $expectedContext->setVersion($this->context->getVersion());
        $expectedContext->getRequestType()->set($this->context->getRequestType());
        $expectedContext->setMetadata($metadata);
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setIncludedEntities($includedEntities);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedEntity);
        $expectedContext->setLastGroup('transform_data');
        $expectedContext->setSoftErrorsHandling(true);

        $actionContext = new UpdateContext($this->configProvider, $this->metadataProvider);
        $actionContext->setMetadata($metadata);
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
                function (UpdateContext $context) use ($expectedContext, $error) {
                    $this->assertEquals($expectedContext, $context);

                    $context->addError($error);
                }
            );
        $this->errorCompleter->expects(self::once())
            ->method('complete')
            ->with(self::identicalTo($error), self::identicalTo($metadata));

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        $this->assertEquals([$error], $actionContext->getErrors());
    }
}
