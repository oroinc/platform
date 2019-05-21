<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use Oro\Component\ChainProcessor\SimpleProcessorRegistry;

class SimpleProcessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testRegistry()
    {
        $parentRegistry = $this->createMock(ProcessorRegistryInterface::class);
        $simpleRegistry = new SimpleProcessorRegistry(
            ['processor1' => ProcessorMock::class],
            $parentRegistry
        );

        $processorFromParentRegistry = new ProcessorMock('processor2');
        $parentRegistry->expects(self::once())
            ->method('getProcessor')
            ->with('processor2')
            ->willReturn($processorFromParentRegistry);

        // test that the simple registry creates a processor
        $processor1 = $simpleRegistry->getProcessor('processor1');
        self::assertInstanceOf(ProcessorMock::class, $processor1);
        // test that the simple registry uses already created instance a processor
        self::assertSame($processor1, $simpleRegistry->getProcessor('processor1'));

        // test that the simple registry delegates getting of a processor to the parent registry
        self::assertSame($processorFromParentRegistry, $simpleRegistry->getProcessor('processor2'));
    }
}
