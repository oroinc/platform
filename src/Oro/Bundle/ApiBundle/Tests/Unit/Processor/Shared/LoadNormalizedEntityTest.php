<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\RequestActionProcessor;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadNormalizedEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextStub;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;

class LoadNormalizedEntityTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $processorBag;

    /** @var LoadNormalizedEntity */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processorBag = $this->getMock('Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface');

        $this->processor = new LoadNormalizedEntity($this->processorBag);
    }

    public function testProcessWhenEntityIdDoesNotExistInContext()
    {
        $this->processorBag->expects($this->never())
            ->method('getProcessor');

        $this->processor->process($this->context);
    }

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
        $getMetadata = new EntityMetadata();
        $getMetadata->set('metadata_key', 'metadata_value');
        $getResponseHeaders = [
            'test-response-header' => 'some response header value'
        ];

        $getContext = new GetContext($this->configProvider, $this->metadataProvider);
        $getProcessor = $this->getMock('Oro\Component\ChainProcessor\ActionProcessorInterface');

        $this->processorBag->expects($this->once())
            ->method('getProcessor')
            ->with('get')
            ->willReturn($getProcessor);
        $getProcessor->expects($this->once())
            ->method('createContext')
            ->willReturn($getContext);

        $this->context->setClassName('Test\Entity');
        $this->context->setId(123);
        $this->context->getRequestHeaders()->set('test-header', 'some value');

        $expectedGetContext = new GetContext($this->configProvider, $this->metadataProvider);
        $expectedGetContext->setVersion($this->context->getVersion());
        $expectedGetContext->getRequestType()->set($this->context->getRequestType());
        $expectedGetContext->setRequestHeaders($this->context->getRequestHeaders());
        $expectedGetContext->setClassName($this->context->getClassName());
        $expectedGetContext->setId($this->context->getId());
        $expectedGetContext->skipGroup('security_check');
        $expectedGetContext->skipGroup(RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $getProcessor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($getContext))
            ->willReturnCallback(
                function (GetContext $context) use (
                    $expectedGetContext,
                    $getResult,
                    $getConfigExtras,
                    $getConfig,
                    $getConfigSections,
                    $getMetadata,
                    $getResponseHeaders
                ) {
                    $this->assertEquals($expectedGetContext, $context);

                    $context->setConfigExtras($getConfigExtras);
                    $context->setConfig($getConfig);
                    foreach ($getConfigSections as $key => $value) {
                        $context->setConfigOf($key, $value);
                    }
                    $context->setMetadata($getMetadata);
                    foreach ($getResponseHeaders as $key => $value) {
                        $context->getResponseHeaders()->set($key, $value);
                    }
                    $context->setResult($getResult);
                }
            );

        $this->processor->process($this->context);

        $expectedContext = new FormContextStub($this->configProvider, $this->metadataProvider);
        $expectedContext->setVersion($this->context->getVersion());
        $expectedContext->getRequestType()->set($this->context->getRequestType());
        $expectedContext->setRequestHeaders($this->context->getRequestHeaders());
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
        $expectedContext->setResult($getResult);

        $this->assertEquals($expectedContext, $this->context);
    }

    public function testProcessWhenGetActionHasErrors()
    {
        $getError = Error::create('test error');

        $getContext = new GetContext($this->configProvider, $this->metadataProvider);
        $getProcessor = $this->getMock('Oro\Component\ChainProcessor\ActionProcessorInterface');

        $this->processorBag->expects($this->once())
            ->method('getProcessor')
            ->with('get')
            ->willReturn($getProcessor);
        $getProcessor->expects($this->once())
            ->method('createContext')
            ->willReturn($getContext);

        $this->context->setClassName('Test\Entity');
        $this->context->setId(123);

        $getProcessor->expects($this->once())
            ->method('process')
            ->willReturnCallback(
                function (GetContext $context) use ($getError) {
                    $context->addError($getError);
                }
            );

        $this->processor->process($this->context);

        $this->assertNull(
            $this->context->getResult()
        );
        $this->assertEquals(
            [$getError],
            $this->context->getErrors()
        );
    }
}
