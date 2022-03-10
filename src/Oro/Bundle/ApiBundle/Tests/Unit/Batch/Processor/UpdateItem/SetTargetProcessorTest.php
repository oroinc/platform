<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\SetTargetProcessor;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

class SetTargetProcessorTest extends BatchUpdateItemProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionProcessorBagInterface */
    private $processorBag;

    /** @var SetTargetProcessor */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processorBag = $this->createMock(ActionProcessorBagInterface::class);

        $this->processor = new SetTargetProcessor($this->processorBag);
    }

    public function testProcessWhenTargetProcessorAlreadySet()
    {
        $targetProcessor = $this->createMock(ActionProcessorInterface::class);

        $this->processorBag->expects(self::never())
            ->method('getProcessor');

        $this->context->setTargetProcessor($targetProcessor);
        $this->processor->process($this->context);

        self::assertSame($targetProcessor, $this->context->getTargetProcessor());
    }

    public function testProcessWhenTargetActionIsNotSet()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The target action is not defined.');

        $this->processor->process($this->context);
    }

    public function testProcessTargetActionIsSetAndTargetProcessorIsNotSet()
    {
        $targetProcessor = $this->createMock(ActionProcessorInterface::class);

        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with('test_action')
            ->willReturn($targetProcessor);

        $this->context->setTargetAction('test_action');
        $this->processor->process($this->context);

        self::assertSame($targetProcessor, $this->context->getTargetProcessor());
    }
}
