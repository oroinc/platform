<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityManagerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class EntityManagerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var EntityManagerPass */
    private $compilerPass;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerBuilder::class);

        $this->compilerPass = new EntityManagerPass();
    }

    public function testProcess(): void
    {
        $definition = $this->createMock(Definition::class);
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('setLogger', [new Reference('logger')]);

        $this->container->expects($this->once())
            ->method('findDefinition')
            ->with('doctrine.orm.entity_manager')
            ->willReturn($definition);

        $this->compilerPass->process($this->container);
    }
}
