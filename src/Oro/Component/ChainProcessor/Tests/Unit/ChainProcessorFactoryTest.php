<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ChainProcessorFactory;

class ChainProcessorFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactory()
    {
        $processor1 = $this->getMock('Oro\Component\ChainProcessor\ProcessorInterface');
        $processor2 = $this->getMock('Oro\Component\ChainProcessor\ProcessorInterface');

        $factory1 = $this->getMock('Oro\Component\ChainProcessor\ProcessorFactoryInterface');
        $factory2 = $this->getMock('Oro\Component\ChainProcessor\ProcessorFactoryInterface');

        $chainFactory = new ChainProcessorFactory();
        $chainFactory->addFactory($factory2);
        $chainFactory->addFactory($factory1, 10);

        $factory1->expects($this->exactly(3))
            ->method('getProcessor')
            ->willReturnMap(
                [
                    ['processor1', $processor1]
                ]
            );
        $factory2->expects($this->exactly(2))
            ->method('getProcessor')
            ->willReturnMap(
                [
                    ['processor2', $processor2]
                ]
            );

        $this->assertSame($processor1, $chainFactory->getProcessor('processor1'));
        $this->assertSame($processor2, $chainFactory->getProcessor('processor2'));
        $this->assertNull($chainFactory->getProcessor('unknown_processor'));
    }
}
