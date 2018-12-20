<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\SimpleProcessorFactory;

class SimpleProcessorFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testFactory()
    {
        $factory = new SimpleProcessorFactory(
            ['processor1' => ProcessorMock::class]
        );
        $factory->addProcessor('processor2', ProcessorMock::class);

        $processor1 = $factory->getProcessor('processor1');
        self::assertInstanceOf(ProcessorMock::class, $processor1);
        self::assertSame($processor1, $factory->getProcessor('processor1'));

        $processor2 = $factory->getProcessor('processor2');
        self::assertInstanceOf(ProcessorMock::class, $processor2);
        self::assertSame($processor2, $factory->getProcessor('processor2'));

        self::assertNull($factory->getProcessor('unknown_processor'));
    }
}
