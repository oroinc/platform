<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultActionProcessor;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadNormalizedIncludedEntities;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

class LoadNormalizedIncludedEntitiesTest extends FormProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionProcessorBagInterface */
    private $processorBag;

    /** @var LoadNormalizedIncludedEntities */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processorBag = $this->createMock(ActionProcessorBagInterface::class);

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
        $includedEntity = new Group();
        $includedEntity->setId(111);
        $includedEntityClass = Group::class;
        $includedEntityId = 'testId';
        $includedRealEntityId = $includedEntity->getId();
        $includedEntityData = new IncludedEntityData('/included/0', 0);
        $includedEntities->add($includedEntity, $includedEntityClass, $includedEntityId, $includedEntityData);

        $createMetadata = new EntityMetadata();
        $createMetadata->setIdentifierFieldNames(['id']);
        $includedEntityData->setMetadata($createMetadata);

        $getResult = ['normalizedKey' => 'normalizedValue'];
        $getMetadata = new EntityMetadata();
        $getMetadata->set('metadata_key', 'metadata_value');

        $getContext = new GetContext($this->configProvider, $this->metadataProvider);
        $getContext->setMetadata($getMetadata);
        $getProcessor = $this->createMock(ActionProcessorInterface::class);

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
        $expectedGetContext->setMasterRequest(false);
        $expectedGetContext->setCorsRequest(false);
        $expectedGetContext->setHateoas(true);
        $expectedGetContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedGetContext->setClassName($includedEntityClass);
        $expectedGetContext->setId($includedRealEntityId);
        $expectedGetContext->setResult($includedEntity);
        $expectedGetContext->skipGroup('security_check');
        $expectedGetContext->skipGroup(NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $expectedGetContext->setSoftErrorsHandling(true);
        $expectedGetContext->setMetadata($getMetadata);

        $getProcessor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($getContext))
            ->willReturnCallback(
                function (GetContext $context) use ($expectedGetContext, $getResult) {
                    self::assertEquals($expectedGetContext, $context);

                    $context->setResult($getResult);
                }
            );

        $this->context->setIncludedData(['key' => 'value']);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->setClassName('Test\Entity');
        $this->context->setId(123);
        $this->context->setMasterRequest(true);
        $this->context->setCorsRequest(true);
        $this->context->setHateoas(true);
        $this->context->getRequestHeaders()->set('test-header', 'some value');
        $this->processor->process($this->context);

        self::assertSame(
            array_merge($getResult, [LoadNormalizedIncludedEntities::INCLUDE_ID_PROPERTY => $includedEntityId]),
            $includedEntityData->getNormalizedData()
        );
        $metadata = $includedEntityData->getMetadata();
        self::assertSame($getMetadata, $metadata);
        self::assertTrue($metadata->hasMetaProperty(LoadNormalizedIncludedEntities::INCLUDE_ID_PROPERTY));
        self::assertEquals(
            LoadNormalizedIncludedEntities::INCLUDE_ID_META,
            $metadata->getMetaProperty(LoadNormalizedIncludedEntities::INCLUDE_ID_PROPERTY)->getResultName()
        );
    }

    public function testProcessForExistingIncludedEntityWhenGetActionSuccess()
    {
        $includedEntities = new IncludedEntityCollection();
        $includedEntity = new Group();
        $includedEntity->setId(111);
        $includedEntityClass = Group::class;
        $includedEntityId = 'testId';
        $includedRealEntityId = $includedEntity->getId();
        $includedEntityData = new IncludedEntityData('/included/0', 0, true);
        $includedEntities->add($includedEntity, $includedEntityClass, $includedEntityId, $includedEntityData);

        $createMetadata = new EntityMetadata();
        $createMetadata->setIdentifierFieldNames(['id']);
        $includedEntityData->setMetadata($createMetadata);

        $getResult = ['normalizedKey' => 'normalizedValue'];
        $getMetadata = new EntityMetadata();
        $getMetadata->set('metadata_key', 'metadata_value');

        $getContext = new GetContext($this->configProvider, $this->metadataProvider);
        $getContext->setMetadata($getMetadata);
        $getProcessor = $this->createMock(ActionProcessorInterface::class);

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
        $expectedGetContext->setMasterRequest(false);
        $expectedGetContext->setCorsRequest(false);
        $expectedGetContext->setHateoas(true);
        $expectedGetContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedGetContext->setClassName($includedEntityClass);
        $expectedGetContext->setId($includedRealEntityId);
        $expectedGetContext->skipGroup('security_check');
        $expectedGetContext->skipGroup(NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $expectedGetContext->setSoftErrorsHandling(true);
        $expectedGetContext->setMetadata($getMetadata);

        $getProcessor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($getContext))
            ->willReturnCallback(
                function (GetContext $context) use ($expectedGetContext, $getResult) {
                    self::assertEquals($expectedGetContext, $context);

                    $context->setResult($getResult);
                }
            );

        $this->context->setIncludedData(['key' => 'value']);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->setClassName('Test\Entity');
        $this->context->setId(123);
        $this->context->setMasterRequest(true);
        $this->context->setCorsRequest(true);
        $this->context->setHateoas(true);
        $this->context->getRequestHeaders()->set('test-header', 'some value');
        $this->processor->process($this->context);

        self::assertSame(
            array_merge($getResult, [LoadNormalizedIncludedEntities::INCLUDE_ID_PROPERTY => $includedEntityId]),
            $includedEntityData->getNormalizedData()
        );
        $metadata = $includedEntityData->getMetadata();
        self::assertSame($getMetadata, $metadata);
        self::assertTrue($metadata->hasMetaProperty(LoadNormalizedIncludedEntities::INCLUDE_ID_PROPERTY));
        self::assertEquals(
            LoadNormalizedIncludedEntities::INCLUDE_ID_META,
            $metadata->getMetaProperty(LoadNormalizedIncludedEntities::INCLUDE_ID_PROPERTY)->getResultName()
        );
    }

    public function testProcessWhenGetActionHasErrors()
    {
        $getError = Error::create('test error');

        $includedEntities = new IncludedEntityCollection();
        $includedEntity = new Group();
        $includedEntity->setId(111);
        $includedEntityClass = Group::class;
        $includedEntityId = 'testId';
        $includedEntityData = new IncludedEntityData('/included/0', 0, true);
        $includedEntities->add($includedEntity, $includedEntityClass, $includedEntityId, $includedEntityData);

        $createMetadata = new EntityMetadata();
        $createMetadata->setIdentifierFieldNames(['id']);
        $includedEntityData->setMetadata($createMetadata);

        $getContext = new GetContext($this->configProvider, $this->metadataProvider);
        $getProcessor = $this->createMock(ActionProcessorInterface::class);

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
        self::assertSame($createMetadata, $includedEntityData->getMetadata());
    }
}
