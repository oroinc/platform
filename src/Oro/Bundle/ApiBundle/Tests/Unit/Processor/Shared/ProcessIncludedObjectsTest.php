<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Collection\IncludedObjectCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedObjectData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Shared\ProcessIncludedObjects;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class ProcessIncludedObjectsTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $processorBag;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $errorCompleter;

    /** @var ProcessIncludedObjects */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processorBag = $this->getMock(ActionProcessorBagInterface::class);
        $this->errorCompleter = $this->getMock(ErrorCompleterInterface::class);

        $this->processor = new ProcessIncludedObjectsStub(
            $this->processorBag,
            $this->errorCompleter
        );
    }

    public function testProcessWhenIncludedDataIsEmpty()
    {
        $this->context->setIncludedData([]);
        $this->context->setIncludedObjects(new IncludedObjectCollection());
        $this->processor->process($this->context);
    }

    public function testProcessWhenIncludedObjectCollectionDoesNotExist()
    {
        $includedData = [
            ['data' => ['type' => 'testType', 'id' => 'testId']]
        ];

        $this->context->setIncludedData($includedData);
        $this->processor->process($this->context);
    }

    public function testProcessForNewIncludedObject()
    {
        $includedData = [
            ['data' => ['type' => 'testType', 'id' => 'testId']]
        ];
        $includedObject = new \stdClass();

        $includedObjects = new IncludedObjectCollection();
        $includedObjects->add(
            $includedObject,
            'Test\Class',
            'id',
            new IncludedObjectData('/included/0', 0)
        );

        $metadata = new EntityMetadata();
        $error = Error::createValidationError('some error');

        $expectedContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $expectedContext->setVersion($this->context->getVersion());
        $expectedContext->getRequestType()->set($this->context->getRequestType());
        $expectedContext->setMetadata($metadata);
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setIncludedObjects($includedObjects);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedObject);
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
        $this->context->setIncludedObjects($includedObjects);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        $this->assertEquals([$error], $actionContext->getErrors());
    }

    public function testProcessForExistingIncludedObject()
    {
        $includedData = [
            ['data' => ['type' => 'testType', 'id' => 'testId']]
        ];
        $includedObject = new \stdClass();

        $includedObjects = new IncludedObjectCollection();
        $includedObjects->add(
            $includedObject,
            'Test\Class',
            'id',
            new IncludedObjectData('/included/0', 0, true)
        );

        $metadata = new EntityMetadata();
        $error = Error::createValidationError('some error');

        $expectedContext = new UpdateContext($this->configProvider, $this->metadataProvider);
        $expectedContext->setVersion($this->context->getVersion());
        $expectedContext->getRequestType()->set($this->context->getRequestType());
        $expectedContext->setMetadata($metadata);
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setIncludedObjects($includedObjects);
        $expectedContext->setClassName('Test\Class');
        $expectedContext->setId('id');
        $expectedContext->setRequestData(['data' => ['type' => 'testType', 'id' => 'testId']]);
        $expectedContext->setResult($includedObject);
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
        $this->context->setIncludedObjects($includedObjects);
        $this->context->getRequestHeaders()->set('header1', 'value1');
        $this->processor->process($this->context);
        $this->assertEquals([$error], $actionContext->getErrors());
    }
}
