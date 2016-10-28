<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\RequestActionProcessor;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadNormalizedIncludedEntities;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class LoadNormalizedIncludedEntitiesTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $processorBag;

    /** @var LoadNormalizedIncludedEntities */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->processorBag = $this->getMock(ActionProcessorBagInterface::class);

        $this->processor = new LoadNormalizedIncludedEntities($this->processorBag);
    }

    public function testProcessWithoutIncludedData()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWithoutIncludedEntities()
    {
        $this->context->setIncludedData(['key' => 'value']);
        $this->processor->process($this->context);
    }

    public function testProcessForNewIncludedEntityWhenGetActionSuccess()
    {
        $includedEntities = new IncludedEntityCollection();
        $includedEntity = new \stdClass();
        $includedEntityClass = 'Test\Class';
        $includedEntityId = 'testId';
        $includedEntityData = new IncludedEntityData('/included/0', 0);
        $includedEntities->add($includedEntity, $includedEntityClass, $includedEntityId, $includedEntityData);

        $getResult = ['normalizedKey' => 'normalizedValue'];
        $getMetadata = new EntityMetadata();
        $getMetadata->set('metadata_key', 'metadata_value');

        $getContext = new GetContext($this->configProvider, $this->metadataProvider);
        $getContext->setMetadata($getMetadata);
        $getProcessor = $this->getMock(ActionProcessorInterface::class);

        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiActions::GET)
            ->willReturn($getProcessor);
        $getProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($getContext);

        $expectedGetContext = new GetContext($this->configProvider, $this->metadataProvider);
        $expectedGetContext->setVersion($this->context->getVersion());
        $expectedGetContext->getRequestType()->set($this->context->getRequestType());
        $expectedGetContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedGetContext->setClassName($includedEntityClass);
        $expectedGetContext->setId($includedEntityId);
        $expectedGetContext->setResult($includedEntity);
        $expectedGetContext->skipGroup('security_check');
        $expectedGetContext->skipGroup(RequestActionProcessor::NORMALIZE_RESULT_GROUP);
        $expectedGetContext->setSoftErrorsHandling(true);
        $expectedGetContext->setMetadata($getMetadata);

        $getProcessor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($getContext))
            ->willReturnCallback(
                function (GetContext $context) use ($expectedGetContext, $getResult, $getMetadata) {
                    self::assertEquals($expectedGetContext, $context);

                    $context->setResult($getResult);
                }
            );

        $this->context->setIncludedData(['key' => 'value']);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->setClassName('Test\Entity');
        $this->context->setId(123);
        $this->context->getRequestHeaders()->set('test-header', 'some value');
        $this->processor->process($this->context);

        self::assertSame($getResult, $includedEntityData->getNormalizedData());
        self::assertSame($getMetadata, $includedEntityData->getMetadata());
    }

    public function testProcessForExistingIncludedEntityWhenGetActionSuccess()
    {
        $includedEntities = new IncludedEntityCollection();
        $includedEntity = new \stdClass();
        $includedEntityClass = 'Test\Class';
        $includedEntityId = 'testId';
        $includedEntityData = new IncludedEntityData('/included/0', 0, true);
        $includedEntities->add($includedEntity, $includedEntityClass, $includedEntityId, $includedEntityData);

        $getResult = ['normalizedKey' => 'normalizedValue'];
        $getMetadata = new EntityMetadata();
        $getMetadata->set('metadata_key', 'metadata_value');

        $getContext = new GetContext($this->configProvider, $this->metadataProvider);
        $getContext->setMetadata($getMetadata);
        $getProcessor = $this->getMock(ActionProcessorInterface::class);

        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiActions::GET)
            ->willReturn($getProcessor);
        $getProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($getContext);

        $expectedGetContext = new GetContext($this->configProvider, $this->metadataProvider);
        $expectedGetContext->setVersion($this->context->getVersion());
        $expectedGetContext->getRequestType()->set($this->context->getRequestType());
        $expectedGetContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedGetContext->setClassName($includedEntityClass);
        $expectedGetContext->setId($includedEntityId);
        $expectedGetContext->skipGroup('security_check');
        $expectedGetContext->skipGroup(RequestActionProcessor::NORMALIZE_RESULT_GROUP);
        $expectedGetContext->setSoftErrorsHandling(true);
        $expectedGetContext->setMetadata($getMetadata);

        $getProcessor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($getContext))
            ->willReturnCallback(
                function (GetContext $context) use ($expectedGetContext, $getResult, $getMetadata) {
                    self::assertEquals($expectedGetContext, $context);

                    $context->setResult($getResult);
                }
            );

        $this->context->setIncludedData(['key' => 'value']);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->setClassName('Test\Entity');
        $this->context->setId(123);
        $this->context->getRequestHeaders()->set('test-header', 'some value');
        $this->processor->process($this->context);

        self::assertSame($getResult, $includedEntityData->getNormalizedData());
        self::assertSame($getMetadata, $includedEntityData->getMetadata());
    }

    public function testProcessWhenGetActionHasErrors()
    {
        $getError = Error::create('test error');

        $includedEntities = new IncludedEntityCollection();
        $includedEntity = new \stdClass();
        $includedEntityClass = 'Test\Class';
        $includedEntityId = 'testId';
        $includedEntityData = new IncludedEntityData('/included/0', 0, true);
        $includedEntities->add($includedEntity, $includedEntityClass, $includedEntityId, $includedEntityData);

        $getContext = new GetContext($this->configProvider, $this->metadataProvider);
        $getProcessor = $this->getMock(ActionProcessorInterface::class);

        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiActions::GET)
            ->willReturn($getProcessor);
        $getProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($getContext);

        $getProcessor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($getContext))
            ->willReturnCallback(
                function (GetContext $context) use ($getError) {
                    $context->addError($getError);
                }
            );

        $this->context->setIncludedData(['key' => 'value']);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->setClassName('Test\Entity');
        $this->context->setId(123);
        $this->context->getRequestHeaders()->set('test-header', 'some value');
        $this->processor->process($this->context);

        self::assertEquals([$getError], $this->context->getErrors());
        self::assertNull($includedEntityData->getNormalizedData());
        self::assertNull($includedEntityData->getMetadata());
    }
}
