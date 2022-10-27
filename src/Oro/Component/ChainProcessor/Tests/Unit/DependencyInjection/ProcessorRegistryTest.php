<?php

namespace Oro\Component\ChainProcessor\Tests\Unit\DependencyInjection;

use Oro\Component\ChainProcessor\DependencyInjection\ProcessorRegistry;
use Oro\Component\ChainProcessor\Tests\Unit\ProcessorMock;
use Psr\Container\ContainerInterface;

class ProcessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetProcessor()
    {
        $processor1 = new ProcessorMock();

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('get')
            ->with('processor1')
            ->willReturn($processor1);

        $processorRegistry = new ProcessorRegistry($container);

        $this->assertSame($processor1, $processorRegistry->getProcessor('processor1'));
    }
}
