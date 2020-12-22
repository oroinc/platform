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
            ['processor1' => ProcessorMock::class, 'processor2' => [ProcessorMock::class, ['test']]],
            $parentRegistry
        );

        $processorFromParentRegistry = new ProcessorMock('processor3');
        $parentRegistry->expects(self::once())
            ->method('getProcessor')
            ->with('processor3')
            ->willReturn($processorFromParentRegistry);

        // test that the simple registry creates a processor without arguments
        /** @var ProcessorMock $processor1 */
        $processor1 = $simpleRegistry->getProcessor('processor1');
        self::assertInstanceOf(ProcessorMock::class, $processor1);
        self::assertNull($processor1->getProcessorId());
        // test that the simple registry uses already created instance a processor
        self::assertSame($processor1, $simpleRegistry->getProcessor('processor1'));

        // test that the simple registry creates a processor with arguments
        /** @var ProcessorMock $processor2 */
        $processor2 = $simpleRegistry->getProcessor('processor2');
        self::assertInstanceOf(ProcessorMock::class, $processor2);
        self::assertSame('test', $processor2->getProcessorId());
        // test that the simple registry uses already created instance a processor
        self::assertSame($processor2, $simpleRegistry->getProcessor('processor2'));

        // test that the simple registry delegates getting of a processor to the parent registry
        self::assertSame($processorFromParentRegistry, $simpleRegistry->getProcessor('processor3'));
    }
}
