<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\UpdateList\LoadNormalizedAsyncOperation;
use Oro\Bundle\ApiBundle\Processor\UpdateList\UpdateListContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ParameterBag;

class LoadNormalizedAsyncOperationTest extends UpdateListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionProcessorBagInterface */
    private $processorBag;

    /** @var ParameterBag */
    private $sharedData;

    /** @var LoadNormalizedAsyncOperation */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sharedData = new ParameterBag();
        $this->sharedData->set('someKey', 'someSharedValue');
        $this->context->setSharedData($this->sharedData);

        $this->processorBag = $this->createMock(ActionProcessorBagInterface::class);

        $this->processor = new LoadNormalizedAsyncOperation($this->processorBag);
    }

    public function testProcessWhenContextAlreadyContainsResult()
    {
        $result = ['key' => 'value'];

        $this->processorBag->expects(self::never())
            ->method('getProcessor');

        $this->context->setResult($result);
        $this->processor->process($this->context);

        self::assertEquals($result, $this->context->getResult());
    }

    public function testProcessWhenGetActionSuccess()
    {
        $getResult = ['key' => 'value'];
        $getConfig = new EntityDefinitionConfig();
        $getConfig->set('config_key', 'config_value');
        $getMetadata = new EntityMetadata('Test\Entity');
        $getMetadata->set('metadata_key', 'metadata_value');

        $getContext = new GetContext($this->configProvider, $this->metadataProvider);
        $getProcessor = $this->createMock(ActionProcessorInterface::class);

        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with('get')
            ->willReturn($getProcessor);
        $getProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($getContext);

        $this->context->setClassName('Test\Entity');
        $this->context->setOperationId(123);
        $this->context->getRequestHeaders()->set('test-header', 'some value');
        $this->context->setHateoas(true);

        $expectedGetContext = new GetContext($this->configProvider, $this->metadataProvider);
        $expectedGetContext->setVersion($this->context->getVersion());
        $expectedGetContext->getRequestType()->set($this->context->getRequestType());
        $expectedGetContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedGetContext->setSharedData($this->sharedData);
        $expectedGetContext->setHateoas(true);
        $expectedGetContext->setClassName(AsyncOperation::class);
        $expectedGetContext->setId($this->context->getOperationId());
        $expectedGetContext->skipGroup(ApiActionGroup::SECURITY_CHECK);
        $expectedGetContext->skipGroup(ApiActionGroup::DATA_SECURITY_CHECK);
        $expectedGetContext->skipGroup(ApiActionGroup::NORMALIZE_RESULT);
        $expectedGetContext->setSoftErrorsHandling(true);

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

        $expectedContext = new UpdateListContext($this->configProvider, $this->metadataProvider);
        $expectedContext->setVersion($this->context->getVersion());
        $expectedContext->getRequestType()->set($this->context->getRequestType());
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setSharedData($this->sharedData);
        $expectedContext->setHateoas($this->context->isHateoasEnabled());
        $expectedContext->setOperationId($this->context->getOperationId());
        $expectedContext->setClassName($this->context->getClassName());
        $expectedContext->setConfigExtras($this->context->getConfigExtras());
        $expectedContext->setConfig($getConfig);
        $expectedContext->setMetadata($getMetadata);
        $expectedContext->setResult($getResult);

        self::assertEquals($expectedContext, $this->context);
    }

    public function testProcessWhenGetActionHasErrors()
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

        $this->context->setClassName('Test\Entity');
        $this->context->setOperationId(123);

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
