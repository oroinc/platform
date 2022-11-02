<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\SetTargetContext;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ParameterBagInterface;

class SetTargetContextTest extends BatchUpdateItemProcessorTestCase
{
    /** @var SetTargetContext */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetTargetContext();
    }

    public function testProcessWhenTargetContextAlreadySet()
    {
        $targetContext = $this->createMock(Context::class);

        $this->context->setTargetContext($targetContext);
        $this->processor->process($this->context);

        self::assertSame($targetContext, $this->context->getTargetContext());
    }

    public function testProcessWhenTargetProcessorIsNotSet()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The target processor is not defined.');

        $this->processor->process($this->context);
    }

    public function testProcessWhenTargetContextIsInstanceOfContextClass()
    {
        $targetContext = new Context(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
        $targetProcessor = $this->createMock(ActionProcessorInterface::class);
        $sharedData = $this->createMock(ParameterBagInterface::class);

        $targetProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($targetContext);

        $this->context->setTargetProcessor($targetProcessor);
        $this->context->setClassName('Test\Entity');
        $this->context->setId(123);
        $this->context->setRequestData(['key' => 'value']);
        $this->context->setSharedData($sharedData);
        $this->processor->process($this->context);

        self::assertSame($targetContext, $this->context->getTargetContext());
        self::assertEquals($this->context->getVersion(), $targetContext->getVersion());
        self::assertEquals([RequestType::REST, RequestType::BATCH], $targetContext->getRequestType()->toArray());
        self::assertEquals($this->context->getClassName(), $targetContext->getClassName());
        self::assertTrue($targetContext->isSoftErrorsHandling());
        self::assertSame($sharedData, $this->context->getSharedData());
    }

    public function testProcessWhenTargetContextIsInstanceOfSingleItemContextClass()
    {
        $targetContext = new SingleItemContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
        $targetProcessor = $this->createMock(ActionProcessorInterface::class);
        $sharedData = $this->createMock(ParameterBagInterface::class);

        $targetProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($targetContext);

        $this->context->setTargetProcessor($targetProcessor);
        $this->context->setClassName('Test\Entity');
        $this->context->setId(123);
        $this->context->setRequestData(['key' => 'value']);
        $this->context->setSharedData($sharedData);
        $this->processor->process($this->context);

        self::assertSame($targetContext, $this->context->getTargetContext());
        self::assertEquals($this->context->getVersion(), $targetContext->getVersion());
        self::assertEquals([RequestType::REST, RequestType::BATCH], $targetContext->getRequestType()->toArray());
        self::assertEquals($this->context->getClassName(), $targetContext->getClassName());
        self::assertSame($this->context->getId(), $targetContext->getId());
        self::assertTrue($targetContext->isSoftErrorsHandling());
        self::assertSame($sharedData, $this->context->getSharedData());
    }

    public function testProcessWhenTargetContextIsInstanceOfFormContextClass()
    {
        $targetContext = new CreateContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
        $targetProcessor = $this->createMock(ActionProcessorInterface::class);
        $sharedData = $this->createMock(ParameterBagInterface::class);

        $targetProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn($targetContext);

        $this->context->setTargetProcessor($targetProcessor);
        $this->context->setClassName('Test\Entity');
        $this->context->setId(123);
        $this->context->setRequestData(['key' => 'value']);
        $this->context->setSharedData($sharedData);
        $this->processor->process($this->context);

        self::assertSame($targetContext, $this->context->getTargetContext());
        self::assertEquals($this->context->getVersion(), $targetContext->getVersion());
        self::assertEquals([RequestType::REST, RequestType::BATCH], $targetContext->getRequestType()->toArray());
        self::assertEquals($this->context->getClassName(), $targetContext->getClassName());
        self::assertSame($this->context->getId(), $targetContext->getId());
        self::assertSame($this->context->getRequestData(), $targetContext->getRequestData());
        self::assertTrue($targetContext->isSoftErrorsHandling());
        self::assertSame($sharedData, $this->context->getSharedData());
    }
}
