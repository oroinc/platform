<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\SimpleProcessorFactory;

class SimpleProcessorFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactory()
    {
        $factory = new SimpleProcessorFactory();

        $factory->addProcessor('processor1', 'Oro\Component\ChainProcessor\Tests\Unit\ProcessorMock');

        $processor1 = $factory->getProcessor('processor1');
        $this->assertInstanceOf('Oro\Component\ChainProcessor\Tests\Unit\ProcessorMock', $processor1);
        $this->assertSame($processor1, $factory->getProcessor('processor1'));

        $this->assertNull($factory->getProcessor('unknown_processor'));
    }
}
