<?php

namespace Oro\Component\ChainProcessor\Tests\Unit\DependencyInjection;

use Oro\Component\ChainProcessor\DependencyInjection\ProcessorRegistry;
use Oro\Component\ChainProcessor\Tests\Unit\ProcessorMock;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ProcessorRegistryTest extends TestCase
{
    public function testGetProcessor(): void
    {
        $processor1 = new ProcessorMock();

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('get')
            ->with('processor1')
            ->willReturn($processor1);

        $processorRegistry = new ProcessorRegistry($container);

        self::assertSame($processor1, $processorRegistry->getProcessor('processor1'));
    }
}
