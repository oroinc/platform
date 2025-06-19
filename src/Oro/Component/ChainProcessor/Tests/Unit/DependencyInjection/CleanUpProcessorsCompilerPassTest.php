<?php

namespace Oro\Component\ChainProcessor\Tests\Unit\DependencyInjection;

use Oro\Component\ChainProcessor\DependencyInjection\CleanUpProcessorsCompilerPass;
use Oro\Component\ChainProcessor\SimpleProcessorRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CleanUpProcessorsCompilerPassTest extends TestCase
{
    private CleanUpProcessorsCompilerPass $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->compiler = new CleanUpProcessorsCompilerPass('simple_factory', 'processor');
    }

    public function testProcessForProcessorWithoutArguments(): void
    {
        $container = new ContainerBuilder();
        $simpleRegistryDef = $container->register('simple_factory', SimpleProcessorRegistry::class)
            ->addArgument([]);
        $container->register('simple_processor', 'Test\SimpleProcessor')
            ->addTag('processor');
        $container->register('abstract_processor')
            ->setAbstract(true)
            ->addTag('processor');
        $container->register('lazy_processor', 'Test\LazyProcessor')
            ->setLazy(true)
            ->addTag('processor');

        $this->compiler->process($container);

        self::assertFalse($container->hasDefinition('simple_processor'));
        self::assertTrue($container->hasDefinition('abstract_processor'));
        self::assertTrue($container->hasDefinition('lazy_processor'));

        self::assertEquals(
            ['simple_processor' => 'Test\SimpleProcessor'],
            $simpleRegistryDef->getArgument(0)
        );
    }

    /**
     * @dataProvider processorWithSimpleArgumentsDataProvider
     */
    public function testProcessForProcessorWithSimpleArguments(array $arguments): void
    {
        $container = new ContainerBuilder();
        $simpleRegistryDef = $container->register('simple_factory', SimpleProcessorRegistry::class)
            ->addArgument([]);
        $container->register('processor', 'Test\Processor')
            ->setArguments($arguments)
            ->addTag('processor');

        $this->compiler->process($container);

        self::assertFalse($container->hasDefinition('processor'));
        self::assertSame(
            ['processor' => ['Test\Processor', $arguments]],
            $simpleRegistryDef->getArgument(0)
        );
    }

    public function processorWithSimpleArgumentsDataProvider(): array
    {
        return [
            ['arguments' => [null]],
            ['arguments' => ['test']],
            ['arguments' => [false]],
            ['arguments' => [true]],
            ['arguments' => [123]],
            ['arguments' => [12.3]],
            ['arguments' => ['test', 123]]
        ];
    }

    /**
     * @dataProvider processorWithNotSimpleArgumentsDataProvider
     */
    public function testProcessForProcessorWithNotSimpleArguments(array $arguments): void
    {
        $container = new ContainerBuilder();
        $simpleRegistryDef = $container->register('simple_factory', SimpleProcessorRegistry::class)
            ->addArgument([]);
        $container->register('processor', 'Test\Processor')
            ->setArguments($arguments)
            ->addTag('processor');

        $this->compiler->process($container);

        self::assertTrue($container->hasDefinition('processor'));
        self::assertSame([], $simpleRegistryDef->getArgument(0));
    }

    public function processorWithNotSimpleArgumentsDataProvider(): array
    {
        return [
            ['arguments' => [['value']]],
            ['arguments' => [['key' => 'value']]],
            ['arguments' => [new Reference('test')]],
            ['arguments' => ['test', new Reference('test')]]
        ];
    }

    public function testProcessForSimpleFactoryWithoutArguments(): void
    {
        $container = new ContainerBuilder();
        $simpleRegistryDef = $container->register('simple_factory', SimpleProcessorRegistry::class)
            ->addArgument([]);
        $container->register('simple_processor', 'Test\SimpleProcessor')
            ->addTag('processor');

        $this->compiler->process($container);

        self::assertFalse($container->hasDefinition('simple_processor'));

        self::assertEquals(
            ['simple_processor' => 'Test\SimpleProcessor'],
            $simpleRegistryDef->getArgument(0)
        );
    }
}
