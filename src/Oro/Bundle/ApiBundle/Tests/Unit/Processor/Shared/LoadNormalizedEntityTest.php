<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadNormalizedEntity;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextStub;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ParameterBag;

class LoadNormalizedEntityTest extends FormProcessorTestCase
{
    /** @var ActionProcessorBagInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $processorBag;

    /** @var ParameterBag */
    private $sharedData;

    /** @var LoadNormalizedEntity */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sharedData = new ParameterBag();
        $this->sharedData->set('someKey', 'someSharedValue');
        $this->context->setSharedData($this->sharedData);

        $this->processorBag = $this->createMock(ActionProcessorBagInterface::class);

        $this->processor = new LoadNormalizedEntity($this->processorBag);
    }

    public function testProcessForEntityWithoutIdentifierFieldsAndContextContainsNormalizedResult()
    {
        $metadata = new EntityMetadata('Test\Entity');
        $normalizedResult = ['key' => 'value'];

        $this->processorBag->expects(self::never())
            ->method('getProcessor');

        $this->context->setMetadata($metadata);
        $this->context->setResult($normalizedResult);
        $this->processor->process($this->context);

        self::assertEquals($normalizedResult, $this->context->getResult());
        self::assertTrue($this->context->isProcessed(LoadNormalizedEntity::OPERATION_NAME));
    }

    public function testProcessForEntityWithoutIdentifierFieldsAndContextContainsNotNormalizedResult()
    {
        $metadata = new EntityMetadata('Test\Entity');

        $this->processorBag->expects(self::never())
            ->method('getProcessor');

        $this->context->setMetadata($metadata);
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
        self::assertTrue($this->context->isProcessed(LoadNormalizedEntity::OPERATION_NAME));
    }

    public function testProcessWhenEntityIdDoesNotExistInContextButEntityHasIdentifierFields()
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->processorBag->expects(self::never())
            ->method('getProcessor');

        $this->context->setMetadata($metadata);
        $this->context->setResult(['key' => 'value']);
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasResult());
        self::assertFalse($this->context->isProcessed(LoadNormalizedEntity::OPERATION_NAME));
    }

    public function testProcessWhenNormalizedEntityAlreadyLoaded()
    {
        $this->processorBag->expects(self::never())
            ->method('getProcessor')
            ->with('get');

        $this->context->setProcessed(LoadNormalizedEntity::OPERATION_NAME);
        $this->processor->process($this->context);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenGetActionSuccess()
    {
        $getResult = ['key' => 'value'];
        $getConfigExtras = [
            new TestConfigExtra('test_extra'),
            new TestConfigSection('test_section')
        ];
        $getConfig = new EntityDefinitionConfig();
        $getConfig->set('config_key', 'config_value');
        $getConfigSections = [
            'test_section' => ['test_section_key' => 'test_section_value']
        ];
        $getMetadata = new EntityMetadata('Test\Entity');
        $getMetadata->set('metadata_key', 'metadata_value');
        $getResponseHeaders = [
            'test-response-header' => 'some response header value'
        ];
        $getInfoRecords = ['' => ['key' => 'value']];

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
        $this->context->setId(123);
        $this->context->setMasterRequest(true);
        $this->context->setCorsRequest(true);
        $this->context->setHateoas(true);
        $this->context->getRequestHeaders()->set('test-header', 'some value');

        $expectedGetContext = new GetContext($this->configProvider, $this->metadataProvider);
        $expectedGetContext->setVersion($this->context->getVersion());
        $expectedGetContext->getRequestType()->set($this->context->getRequestType());
        $expectedGetContext->setMasterRequest(false);
        $expectedGetContext->setCorsRequest(false);
        $expectedGetContext->setHateoas(true);
        $expectedGetContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedGetContext->setSharedData($this->sharedData);
        $expectedGetContext->setClassName($this->context->getClassName());
        $expectedGetContext->setId($this->context->getId());
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
                    $getConfigExtras,
                    $getConfig,
                    $getConfigSections,
                    $getMetadata,
                    $getResponseHeaders,
                    $getInfoRecords
                ) {
                    self::assertEquals($expectedGetContext, $context);

                    $context->setConfigExtras($getConfigExtras);
                    $context->setConfig($getConfig);
                    foreach ($getConfigSections as $key => $value) {
                        $context->setConfigOf($key, $value);
                    }
                    $context->setMetadata($getMetadata);
                    foreach ($getResponseHeaders as $key => $value) {
                        $context->getResponseHeaders()->set($key, $value);
                    }
                    $context->setInfoRecords($getInfoRecords);
                    $context->setResult($getResult);
                }
            );

        $this->processor->process($this->context);

        $expectedContext = new FormContextStub($this->configProvider, $this->metadataProvider);
        $expectedContext->setAction(ApiAction::UPDATE);
        $expectedContext->setVersion($this->context->getVersion());
        $expectedContext->getRequestType()->set($this->context->getRequestType());
        $expectedContext->setMasterRequest(true);
        $expectedContext->setCorsRequest(true);
        $expectedContext->setHateoas(true);
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedContext->setSharedData($this->sharedData);
        $expectedContext->setId($this->context->getId());
        $expectedContext->setClassName($this->context->getClassName());
        $expectedContext->setConfigExtras($getConfigExtras);
        $expectedContext->setConfig($getConfig);
        foreach ($getConfigSections as $key => $value) {
            $expectedContext->setConfigOf($key, $value);
        }
        $expectedContext->setMetadata($getMetadata);
        foreach ($getResponseHeaders as $key => $value) {
            $expectedContext->getResponseHeaders()->set($key, $value);
        }
        $expectedContext->setInfoRecords($getInfoRecords);
        $expectedContext->setResult($getResult);
        $expectedContext->setProcessed(LoadNormalizedEntity::OPERATION_NAME);

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
        $this->context->setId(123);

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
