<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\ProcessIncludedEntities;
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

    public function testProcessWithCommonFormError()
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

        $expectedError = Error::createValidationError('some error')
            ->setSource(ErrorSource::createByPointer('/included/0'));

        $actionContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $actionContext->setMetadata(new EntityMetadata());
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
                function (CreateContext $context) {
                    $error = Error::createValidationError('some error');
                    $context->addError($error);
                }
            );

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);
        self::assertEquals([$expectedError], $actionContext->getErrors());
    }

    public function testProcessWithFieldFormError()
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

        $expectedError = Error::createValidationError('some error')
            ->setSource(ErrorSource::createByPointer('/included/0/attributes/field1'));

        $actionContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $actionContext->setMetadata(new EntityMetadata());
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
                function (CreateContext $context) {
                    $error = Error::createValidationError('some error')
                        ->setSource(ErrorSource::createByPointer('/data/attributes/field1'));
                    $context->addError($error);
                }
            );

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);
        self::assertEquals([$expectedError], $actionContext->getErrors());
    }

    public function testProcessWithFieldFormErrorRepresentedByPropertyPath()
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

        $expectedError = Error::createValidationError('some error')
            ->setSource(ErrorSource::createByPropertyPath('included.0.field1'));

        $actionContext = new CreateContext($this->configProvider, $this->metadataProvider);
        $actionContext->setMetadata(new EntityMetadata());
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
                function (CreateContext $context) {
                    $error = Error::createValidationError('some error')
                        ->setSource(ErrorSource::createByPropertyPath('field1'));
                    $context->addError($error);
                }
            );

        $this->context->setIncludedData($includedData);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);
        self::assertEquals([$expectedError], $actionContext->getErrors());
    }
}
