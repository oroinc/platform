<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\Compiler;

use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\TestSessionListenerCompilerPass;
use Oro\Bundle\TestFrameworkBundle\EventListener\TestSessionListener;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class TestSessionListenerCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var TestSessionListenerCompilerPass */
    protected $compilerPass;

    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $containerBuilder;

    /** @var Definition|\PHPUnit\Framework\MockObject\MockObject */
    protected $definition;

    protected function setUp()
    {
        $this->compilerPass = new TestSessionListenerCompilerPass();

        $this->containerBuilder = $this->createMock(ContainerBuilder::class);
        $this->definition = $this->createMock(Definition::class);
    }

    public function testProcessWithoutDefinition()
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(TestSessionListenerCompilerPass::TEST_SESSION_LISTENER_SERVICE)
            ->willReturn(false);

        $this->containerBuilder->expects($this->never())->method('getDefinition');
        $this->definition->expects($this->never())->method('setClass');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcess()
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(TestSessionListenerCompilerPass::TEST_SESSION_LISTENER_SERVICE)
            ->willReturn(true);
        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(TestSessionListenerCompilerPass::TEST_SESSION_LISTENER_SERVICE)
            ->willReturn($this->definition);
        $this->definition->expects($this->once())
            ->method('setClass')
            ->with(TestSessionListener::class);

        $this->compilerPass->process($this->containerBuilder);
    }
}
