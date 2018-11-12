<?php

namespace Oro\Component\ChainProcessor\Tests\Unit\DependencyInjection;

use Oro\Component\ChainProcessor\DependencyInjection\ProcessorFactory;
use Oro\Component\ChainProcessor\Tests\Unit\ProcessorMock;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProcessorFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testFactory()
    {
        $processor1 = new ProcessorMock();

        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->at(0))
            ->method('get')
            ->with('processor1', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->willReturn($processor1);
        $container->expects($this->at(1))
            ->method('get')
            ->with('unknown_processor', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->willReturn(null);

        $factory = new ProcessorFactory($container);

        $this->assertSame($processor1, $factory->getProcessor('processor1'));
        $this->assertNull($factory->getProcessor('unknown_processor'));
    }
}
