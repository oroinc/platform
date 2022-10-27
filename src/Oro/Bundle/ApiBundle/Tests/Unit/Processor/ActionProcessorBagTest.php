<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\ActionProcessorBag;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

class ActionProcessorBagTest extends \PHPUnit\Framework\TestCase
{
    private function getActionProcessor(string $action): ActionProcessorInterface
    {
        $processor = $this->createMock(ActionProcessorInterface::class);
        $processor->expects(self::any())
            ->method('getAction')
            ->willReturn($action);

        return $processor;
    }

    public function testGetProcessor()
    {
        $processor = $this->getActionProcessor('test');

        $actionProcessorBag = new ActionProcessorBag();
        $actionProcessorBag->addProcessor($processor);

        self::assertSame($processor, $actionProcessorBag->getProcessor('test'));
    }

    public function testGetUnknownProcessor()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A processor for "unknown" action was not found.');

        $processor = $this->getActionProcessor('test');

        $actionProcessorBag = new ActionProcessorBag();
        $actionProcessorBag->addProcessor($processor);

        $actionProcessorBag->getProcessor('unknown');
    }

    public function testGetActions()
    {
        $actionProcessorBag = new ActionProcessorBag();
        self::assertSame([], $actionProcessorBag->getActions());

        $actionProcessorBag->addProcessor($this->getActionProcessor('test1'));
        self::assertSame(['test1'], $actionProcessorBag->getActions());

        $actionProcessorBag->addProcessor($this->getActionProcessor('unhandled_error'));
        self::assertSame(['test1'], $actionProcessorBag->getActions());

        $actionProcessorBag->addProcessor($this->getActionProcessor('test2'));
        self::assertSame(['test1', 'test2'], $actionProcessorBag->getActions());
    }
}
