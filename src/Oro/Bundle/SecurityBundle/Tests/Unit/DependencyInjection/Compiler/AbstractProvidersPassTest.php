<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

abstract class AbstractProvidersPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder
     */
    protected $container;

    /**
     * @var mixed
     */
    protected $compilerPass;

    /**
     * @var string
     */
    protected $chainServiceId;

    /**
     * @var string
     */
    protected $tagName;

    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->container, $this->compilerPass);
    }

    public function testProcessNotRegisterProvider()
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with($this->chainServiceId)
            ->willReturn(false);
        $this->container->expects($this->never())
            ->method('getDefinition');
        $this->container->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($this->container);
    }

    public function testProcess()
    {
        $definition = $this->createMock('Symfony\Component\DependencyInjection\Definition');
        $definition->expects($this->at(0))
            ->method('addMethodCall')
            ->with('addProvider', ['alias1', new Reference('provider1')]);
        $definition->expects($this->at(1))
            ->method('addMethodCall')
            ->with('addProvider', ['alias2', new Reference('provider2')]);

        $this->container->expects($this->once())
            ->method('has')
            ->with($this->chainServiceId)
            ->willReturn(true);
        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with($this->chainServiceId)
            ->willReturn($definition);
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->tagName)
            ->willReturn([
                'provider1' => [['class' => 'Test\Class1', 'alias' => 'alias1']],
                'provider2' => [['class' => 'Test\Class2', 'alias' => 'alias2']],
            ]);

        $this->compilerPass->process($this->container);
    }

    public function testProcessEmptyProviders()
    {
        $definition = $this->createMock('Symfony\Component\DependencyInjection\Definition');
        $definition->expects($this->never())
            ->method('addMethodCall');

        $this->container->expects($this->once())
            ->method('has')
            ->with($this->chainServiceId)
            ->willReturn(true);
        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with($this->chainServiceId)
            ->willReturn($definition);
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->tagName)
            ->willReturn([]);

        $this->compilerPass->process($this->container);
    }

    /**
     * @expectedException
     * @expectedExceptionMessage
     */
    public function testProcessWithoutAlias()
    {
        $definition = $this->createMock('Symfony\Component\DependencyInjection\Definition');
        $definition->expects($this->never())->method('addMethodCall');

        $this->container->expects($this->once())
            ->method('has')
            ->with($this->chainServiceId)
            ->willReturn(true);
        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with($this->chainServiceId)
            ->willReturn($definition);
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->tagName)
            ->willReturn([
                'provider1' => [['class' => 'Test\Class1']],
            ]);

        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage(
            'Tag ' . $this->tagName . ' alias is missing for provider1 service'
        );

        $this->compilerPass->process($this->container);
    }
}
