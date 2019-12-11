<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\ProfilerStorageCompilerPass;
use Oro\Bundle\PlatformBundle\Profiler\RepeatableFileProfilerStorage;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;

class ProfilerStorageCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var ProfilerStorageCompilerPass */
    private $compilerPass;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerBuilder::class);
        $this->compilerPass = new ProfilerStorageCompilerPass();
    }

    public function testSetProfileStorageDecorator(): void
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with('profiler.storage')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with('profiler.storage')
            ->willReturn(new Definition(FileProfilerStorage::class, ['%profiler.storage.dsn%']));

        $definition = new Definition(RepeatableFileProfilerStorage::class, ['%profiler.storage.dsn%']);
        $definition->setDecoratedService('profiler.storage');

        $this->container->expects($this->once())
            ->method('setDefinition')
            ->with('oro_platform.profiler.storage', $definition);

        $this->compilerPass->process($this->container);
    }

    public function testNotSetDecoratorWhenParentNotExist(): void
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with('profiler.storage')
            ->willReturn(false);

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->container->expects($this->never())
            ->method('setDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testNotSetDecoratorWhenParentNotFileStorage(): void
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with('profiler.storage')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with('profiler.storage')
            ->willReturn(new Definition(\stdClass::class));

        $this->container->expects($this->never())
            ->method('setDefinition');

        $this->compilerPass->process($this->container);
    }
}
