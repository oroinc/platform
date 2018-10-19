<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\LoggerCollectorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class LoggerCollectorPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LoggerCollectorPass
     */
    protected $compilerPass;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->compilerPass = new LoggerCollectorPass();
    }

    public function testProcessWhenServiceNotExist()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder $containerBuilder */
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder->expects($this->any())
            ->method('hasDefinition')
            ->with('data_collector.logger')
            ->willReturn(false);
        $containerBuilder->expects($this->never())->method('getDefinition');

        $this->compilerPass->process($containerBuilder);
    }

    public function testProcessWhenServiceExist()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder $containerBuilder */
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder->expects($this->any())
            ->method('hasDefinition')
            ->with('data_collector.logger')
            ->willReturn(true);
        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->willReturn(new Definition());

        $this->compilerPass->process($containerBuilder);
    }
}
