<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use Oro\Component\ChainProcessor\SimpleProcessorRegistry;

class SimpleProcessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testRegistry()
    {
        $parentRegistry = $this->createMock(ProcessorRegistryInterface::class);
        $processor4Callback = function () {
        };
        $simpleRegistry = new SimpleProcessorRegistry(
            [
                'processor1' => ProcessorMock::class,
                'processor2' => [ProcessorMock::class, ['processor2']],
                'processor4' => [ProcessorMock::class, ['processor4', $processor4Callback]]
            ],
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
        self::assertNull($processor1->getCallback());
        // test that the simple registry uses already created instance a processor
        self::assertSame($processor1, $simpleRegistry->getProcessor('processor1'));

        // test that the simple registry creates a processor with one argument
        /** @var ProcessorMock $processor2 */
        $processor2 = $simpleRegistry->getProcessor('processor2');
        self::assertInstanceOf(ProcessorMock::class, $processor2);
        self::assertSame('processor2', $processor2->getProcessorId());
        self::assertNull($processor2->getCallback());
        // test that the simple registry uses already created instance a processor
        self::assertSame($processor2, $simpleRegistry->getProcessor('processor2'));

        // test that the simple registry creates a processor with several arguments
        /** @var ProcessorMock $processor4 */
        $processor4 = $simpleRegistry->getProcessor('processor4');
        self::assertInstanceOf(ProcessorMock::class, $processor4);
        self::assertSame('processor4', $processor4->getProcessorId());
        self::assertSame($processor4Callback, $processor4->getCallback());
        // test that the simple registry uses already created instance a processor
        self::assertSame($processor4, $simpleRegistry->getProcessor('processor4'));

        // test that the simple registry delegates getting of a processor to the parent registry
        self::assertSame($processorFromParentRegistry, $simpleRegistry->getProcessor('processor3'));
    }
}
