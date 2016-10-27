<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Collection\IncludedObjectCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedObjectData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\ProcessIncludedObjects;
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

        $this->processor = new ProcessIncludedObjects(
            $this->processorBag,
            $this->errorCompleter
        );
    }

    public function testProcessWithCommonFormError()
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

        $expectedError = Error::createValidationError('some error')
            ->setSource(ErrorSource::createByPropertyPath('/included/0'));

        $actionContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $actionContext->setMetadata(new EntityMetadata());
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
                function (CreateContext $context) {
                    $error = Error::createValidationError('some error');
                    $context->addError($error);
                }
            );

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedObjects($includedObjects);
        $this->processor->process($this->context);
        $this->assertEquals([$expectedError], $actionContext->getErrors());
    }

    public function testProcessWithFieldFormError()
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

        $expectedError = Error::createValidationError('some error')
            ->setSource(ErrorSource::createByPointer('/included/0/attributes/field1'));

        $actionContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $actionContext->setMetadata(new EntityMetadata());
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
                function (CreateContext $context) {
                    $error = Error::createValidationError('some error')
                        ->setSource(ErrorSource::createByPointer('/data/attributes/field1'));
                    $context->addError($error);
                }
            );

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedObjects($includedObjects);
        $this->processor->process($this->context);
        $this->assertEquals([$expectedError], $actionContext->getErrors());
    }

    public function testProcessWithFieldFormErrorRepresentedByPropertyPath()
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

        $expectedError = Error::createValidationError('some error')
            ->setSource(ErrorSource::createByPropertyPath('included.0.field1'));

        $actionContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $actionContext->setMetadata(new EntityMetadata());
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
                function (CreateContext $context) {
                    $error = Error::createValidationError('some error')
                        ->setSource(ErrorSource::createByPropertyPath('field1'));
                    $context->addError($error);
                }
            );

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedObjects($includedObjects);
        $this->processor->process($this->context);
        $this->assertEquals([$expectedError], $actionContext->getErrors());
    }
}
