<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\ProfilerStorageCompilerPass;
use Oro\Bundle\PlatformBundle\Profiler\RepeatableFileProfilerStorage;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;

class ProfilerStorageCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProfilerStorageCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ProfilerStorageCompilerPass();
    }

    public function testSetProfileStorageDecorator(): void
    {
        $container = new ContainerBuilder();
        $profilerStorageDef = $container->register('profiler.storage', FileProfilerStorage::class)
            ->addArgument('%profiler.storage.dsn%');

        $this->compiler->process($container);

        $expectedSef = (new Definition(RepeatableFileProfilerStorage::class, $profilerStorageDef->getArguments()))
            ->setDecoratedService('profiler.storage');
        self::assertEquals($expectedSef, $container->getDefinition('oro_platform.profiler.storage'));
    }

    public function testNotSetDecoratorWhenParentNotExist(): void
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);

        self::assertFalse($container->hasDefinition('oro_platform.profiler.storage'));
    }

    public function testNotSetDecoratorWhenParentNotFileStorage(): void
    {
        $container = new ContainerBuilder();
        $container->register('profiler.storage', \stdClass::class);

        $this->compiler->process($container);

        self::assertFalse($container->hasDefinition('oro_platform.profiler.storage'));
    }
}
