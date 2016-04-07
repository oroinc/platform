<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\RequestActionProcessor;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadNormalizedEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

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

        $getContext->setVersion($this->context->getVersion());
        $getContext->getRequestType()->set($this->context->getRequestType()->toArray());
        $getContext->setClassName($this->context->getClassName());
        $getContext->setId($this->context->getId());
        $getContext->skipGroup(RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $getProcessor->expects($this->once())
            ->method('process')
            ->with($getContext)
            ->willReturnCallback(
                function (GetContext $context) use ($getResult) {
                    $context->setResult($getResult);
                }
            );

        $this->processor->process($this->context);

        $this->assertEquals(
            $getResult,
            $this->context->getResult()
        );
        $this->assertFalse(
            $this->context->hasErrors()
        );
    }

    public function testProcessWhenGetActionHasErrors()
    {
        $getError = new Error();
        $getError->setTitle('test error');

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

        $getContext->setVersion($this->context->getVersion());
        $getContext->getRequestType()->set($this->context->getRequestType()->toArray());
        $getContext->setClassName($this->context->getClassName());
        $getContext->setId($this->context->getId());
        $getContext->skipGroup(RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $getProcessor->expects($this->once())
            ->method('process')
            ->with($getContext)
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
