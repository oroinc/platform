<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationship;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationship\LoadNormalizedRelationship;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ParameterBag;

class LoadNormalizedRelationshipTest extends ChangeRelationshipProcessorTestCase
{
    /** @var ActionProcessorBagInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $processorBag;

    /** @var ParameterBag */
    private $sharedData;

    /** @var LoadNormalizedRelationship */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->sharedData = new ParameterBag();
        $this->sharedData->set('someKey', 'someSharedValue');
        $this->context->setSharedData($this->sharedData);

        $this->processorBag = $this->createMock(ActionProcessorBagInterface::class);

        $this->processor = new LoadNormalizedRelationship($this->processorBag);
    }

    public function testProcessWhenNormalizedRelationshipAlreadyLoaded(): void
    {
        $this->processorBag->expects(self::never())
            ->method('getProcessor')
            ->with('get');

        $this->context->setResult(['key' => 'value']);
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoParentEntity(): void
    {
        $this->processorBag->expects(self::never())
            ->method('getProcessor');

        $this->processor->process($this->context);
    }

    /**
     * @dataProvider processWhenGetActionSuccessDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenGetActionSuccess(
        array $associationIdFields,
        bool $isCollection,
        mixed $associationData,
        mixed $normalizedAssociationData
    ): void {
        $normalizedEntityConfigExtras = [
            new ExpandRelatedEntitiesConfigExtra(['association1'])
        ];
        $associationName = 'test';
        $getResult = [$associationName => $associationData];
        $getConfig = new EntityDefinitionConfig();
        $getConfig->addField($associationName)
            ->setTargetEntity(new EntityDefinitionConfig())
            ->setIdentifierFieldNames($associationIdFields);
        $getMetadata = new EntityMetadata('Test\Entity');
        $getMetadata->addAssociation(new AssociationMetadata($associationName))
            ->setTargetMetadata(new EntityMetadata('Test\TargetEntity'));

        $getContext = new GetContext($this->configProvider, $this->metadataProvider);
        $getProcessor = $this->createMock(ActionProcessorInterface::class);

        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with('get')
            ->willReturn($getProcessor);
        $getProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($getContext);

        $this->context->setAssociationName($associationName);
        $this->context->setParentClassName('Test\Entity');
        $this->context->setParentId(123);
        $this->context->setParentEntity(new \stdClass());
        $this->context->setIsCollection($isCollection);
        $this->context->setMasterRequest(true);
        $this->context->setCorsRequest(true);
        $this->context->setHateoas(true);
        $this->context->getRequestHeaders()->set('test-header', 'some value');
        $this->context->setNormalizedEntityConfigExtras($normalizedEntityConfigExtras);

        $expectedGetContext = new GetContext($this->configProvider, $this->metadataProvider);
        $expectedGetContext->setVersion($this->context->getVersion());
        $expectedGetContext->getRequestType()->set($this->context->getRequestType());
        $expectedGetContext->setMasterRequest(false);
        $expectedGetContext->setCorsRequest(false);
        $expectedGetContext->setHateoas(true);
        $expectedGetContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedGetContext->setSharedData($this->sharedData);
        $expectedGetContext->setParentAction($this->context->getAction());
        $expectedGetContext->setClassName($this->context->getParentClassName());
        $expectedGetContext->setId($this->context->getParentId());
        $expectedGetContext->setResult($this->context->getParentEntity());
        $expectedGetContext->addConfigExtra(new FilterFieldsConfigExtra([
            $this->context->getParentClassName() => [$this->context->getAssociationName()]
        ]));
        $expectedGetContext->skipGroup(ApiActionGroup::SECURITY_CHECK);
        $expectedGetContext->skipGroup(ApiActionGroup::DATA_SECURITY_CHECK);
        $expectedGetContext->skipGroup(ApiActionGroup::NORMALIZE_RESULT);
        $expectedGetContext->setSoftErrorsHandling(true);
        foreach ($normalizedEntityConfigExtras as $extra) {
            $expectedGetContext->addConfigExtra($extra);
        }

        $getProcessor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($getContext))
            ->willReturnCallback(
                function (GetContext $context) use (
                    $expectedGetContext,
                    $getResult,
                    $getConfig,
                    $getMetadata
                ) {
                    self::assertEquals($expectedGetContext, $context);
                    $context->setConfig($getConfig);
                    $context->setMetadata($getMetadata);
                    $context->setResult($getResult);
                }
            );

        $this->processor->process($this->context);

        $expectedContext = new ChangeRelationshipContext($this->configProvider, $this->metadataProvider);
        $expectedContext->setAction(ApiAction::UPDATE_RELATIONSHIP);
        $expectedContext->setVersion($this->context->getVersion());
        $expectedContext->getRequestType()->set($this->context->getRequestType());
        $expectedContext->setMasterRequest(true);
        $expectedContext->setCorsRequest(true);
        $expectedContext->setHateoas(true);
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setSharedData($this->sharedData);
        $expectedContext->setAssociationName($this->context->getAssociationName());
        $expectedContext->setParentClassName($this->context->getParentClassName());
        $expectedContext->setParentId($this->context->getParentId());
        $expectedContext->setParentEntity($this->context->getParentEntity());
        $expectedContext->setIsCollection($this->context->isCollection());
        $expectedContext->setNormalizedEntityConfigExtras($normalizedEntityConfigExtras);
        $expectedContext->setConfig($getConfig->getField($associationName)->getTargetEntity());
        $expectedContext->setMetadata($getMetadata->getAssociation($associationName)->getTargetMetadata());
        $expectedContext->setResult($normalizedAssociationData);

        self::assertEquals($expectedContext, $this->context);
    }

    public static function processWhenGetActionSuccessDataProvider(): array
    {
        return [
            'null to-one'                          => [['id'], false, null, null],
            'empty to-many'                        => [['id'], true, [], []],
            'normalized to-one'                    => [['id'], false, ['id' => 1], ['id' => 1]],
            'normalized to-many'                   => [['id'], true, [['id' => 1]], [['id' => 1]]],
            'not normalized to-one'                => [['id'], false, 1, ['id' => 1]],
            'not normalized to-many'               => [['id'], true, [1], [['id' => 1]]],
            'not normalized to-one, no id'         => [[], false, 1, 1],
            'not normalized to-many, no id'        => [[], true, [1], [1]],
            'not normalized to-one, composite id'  => [['id1', 'id2'], false, 1, 1],
            'not normalized to-many, composite id' => [['id1', 'id2'], true, [1], [1]]
        ];
    }

    public function testProcessWhenGetActionHasErrors(): void
    {
        $getError = Error::create('test error');

        $getContext = new GetContext($this->configProvider, $this->metadataProvider);
        $getProcessor = $this->createMock(ActionProcessorInterface::class);

        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with('get')
            ->willReturn($getProcessor);
        $getProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($getContext);

        $this->context->setAssociationName('test');
        $this->context->setParentClassName('Test\Entity');
        $this->context->setParentId(123);
        $this->context->setParentEntity(new \stdClass());

        $getProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (GetContext $context) use ($getError) {
                $context->addError($getError);
            });

        $this->processor->process($this->context);

        self::assertNull($this->context->getResult());
        self::assertEquals([$getError], $this->context->getErrors());
    }
}
