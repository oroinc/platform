<?php

namespace Oro\Component\ChainProcessor\Tests\Unit\DependencyInjection;

use Oro\Component\ChainProcessor\DependencyInjection\CleanUpProcessorsCompilerPass;
use Oro\Component\ChainProcessor\SimpleProcessorRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CleanUpProcessorsCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessForProcessorWithoutArguments()
    {
        $simpleRegistry = new Definition(SimpleProcessorRegistry::class, [[]]);

        $simpleProcessor = new Definition('Test\SimpleProcessor');
        $simpleProcessor->addTag('processor');

        $abstractProcessor = new Definition();
        $abstractProcessor->setAbstract(true);
        $abstractProcessor->addTag('processor');

        $lazyProcessor = new Definition('Test\LazyProcessor');
        $lazyProcessor->setLazy(true);
        $lazyProcessor->addTag('processor');

        $container = new ContainerBuilder();
        $container->addDefinitions([
            'simple_factory'     => $simpleRegistry,
            'simple_processor'   => $simpleProcessor,
            'abstract_processor' => $abstractProcessor,
            'lazy_processor'     => $lazyProcessor
        ]);

        $compilerPass = new CleanUpProcessorsCompilerPass('simple_factory', 'processor');
        $compilerPass->process($container);

        self::assertFalse($container->hasDefinition('simple_processor'));
        self::assertTrue($container->hasDefinition('abstract_processor'));
        self::assertTrue($container->hasDefinition('lazy_processor'));

        self::assertEquals(
            ['simple_processor' => 'Test\SimpleProcessor'],
            $simpleRegistry->getArgument(0)
        );
    }

    /**
     * @dataProvider processorWithSimpleArgumentsDataProvider
     */
    public function testProcessForProcessorWithSimpleArguments(array $arguments)
    {
        $simpleRegistry = new Definition(SimpleProcessorRegistry::class, [[]]);

        $processor = new Definition('Test\Processor', $arguments);
        $processor->addTag('processor');

        $container = new ContainerBuilder();
        $container->addDefinitions([
            'simple_factory' => $simpleRegistry,
            'processor'      => $processor
        ]);

        $compilerPass = new CleanUpProcessorsCompilerPass('simple_factory', 'processor');
        $compilerPass->process($container);

        self::assertFalse($container->hasDefinition('processor'));
        self::assertSame(
            ['processor' => ['Test\Processor', $arguments]],
            $simpleRegistry->getArgument(0)
        );
    }

    public function processorWithSimpleArgumentsDataProvider()
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
    public function testProcessForProcessorWithNotSimpleArguments(array $arguments)
    {
        $simpleRegistry = new Definition(SimpleProcessorRegistry::class, [[]]);

        $processor = new Definition('Test\Processor', $arguments);
        $processor->addTag('processor');

        $container = new ContainerBuilder();
        $container->addDefinitions([
            'simple_factory' => $simpleRegistry,
            'processor'      => $processor
        ]);

        $compilerPass = new CleanUpProcessorsCompilerPass('simple_factory', 'processor');
        $compilerPass->process($container);

        self::assertTrue($container->hasDefinition('processor'));
        self::assertSame([], $simpleRegistry->getArgument(0));
    }

    public function processorWithNotSimpleArgumentsDataProvider()
    {
        return [
            ['arguments' => [['value']]],
            ['arguments' => [['key' => 'value']]],
            ['arguments' => [new Reference('test')]],
            ['arguments' => ['test', new Reference('test')]]
        ];
    }

    public function testProcessForSimpleFactoryWithoutArguments()
    {
        $simpleRegistry = new Definition(SimpleProcessorRegistry::class, []);

        $simpleProcessor = new Definition('Test\SimpleProcessor');
        $simpleProcessor->addTag('processor');

        $container = new ContainerBuilder();
        $container->addDefinitions([
            'simple_factory'   => $simpleRegistry,
            'simple_processor' => $simpleProcessor
        ]);

        $compilerPass = new CleanUpProcessorsCompilerPass('simple_factory', 'processor');
        $compilerPass->process($container);

        self::assertFalse($container->hasDefinition('simple_processor'));

        self::assertEquals(
            ['simple_processor' => 'Test\SimpleProcessor'],
            $simpleRegistry->getArgument(0)
        );
    }
}
