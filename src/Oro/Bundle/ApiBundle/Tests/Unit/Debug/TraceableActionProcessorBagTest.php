<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Debug;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\Debug\TraceableActionProcessor;
use Oro\Component\ChainProcessor\Debug\TraceLogger;
use Oro\Bundle\ApiBundle\Debug\TraceableActionProcessorBag;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;

class TraceableActionProcessorBagTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionProcessorBagInterface */
    protected $processorBag;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TraceLogger */
    protected $logger;

    /** @var TraceableActionProcessorBag */
    protected $traceableProcessorBag;

    protected function setUp()
    {
        $this->processorBag = $this->createMock(ActionProcessorBagInterface::class);
        $this->logger = $this->createMock(TraceLogger::class);

        $this->traceableProcessorBag = new TraceableActionProcessorBag($this->processorBag, $this->logger);
    }

    public function testAddProcessor()
    {
        $processor = $this->createMock(ActionProcessorInterface::class);

        $this->processorBag->expects(self::once())
            ->method('addProcessor')
            ->with(self::isInstanceOf(TraceableActionProcessor::class));

        $this->traceableProcessorBag->addProcessor($processor);
    }

    public function testGetProcessor()
    {
        $action = 'testAction';
        $processor = $this->createMock(ActionProcessorInterface::class);

        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with($action)
            ->willReturn($processor);

        self::assertSame(
            $processor,
            $this->traceableProcessorBag->getProcessor($action)
        );
    }

    public function testGetActions()
    {
        $actions = ['testAction'];

        $this->processorBag->expects(self::once())
            ->method('getActions')
            ->willReturn($actions);

        self::assertSame(
            $actions,
            $this->traceableProcessorBag->getActions()
        );
    }
}
