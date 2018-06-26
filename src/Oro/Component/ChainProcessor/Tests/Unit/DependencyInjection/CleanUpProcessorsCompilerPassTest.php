<?php

namespace Oro\Component\ChainProcessor\Tests\Unit\DependencyInjection;

use Oro\Component\ChainProcessor\DependencyInjection\CleanUpProcessorsCompilerPass;
use Oro\Component\ChainProcessor\SimpleProcessorFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class CleanUpProcessorsCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessWithoutSimpleFactory()
    {
        $container = new ContainerBuilder();

        $simpleProcessor = new Definition('Test\SimpleProcessor');
        $simpleProcessor->addTag('processor');

        $container->addDefinitions([
            'simple_processor' => $simpleProcessor,
        ]);

        $compilerPass = new CleanUpProcessorsCompilerPass(
            'simple_factory',
            'processor'
        );

        $compilerPass->process($container);

        self::assertTrue($container->hasDefinition('simple_processor'));
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();

        $simpleFactory = new Definition(SimpleProcessorFactory::class, [[]]);

        $simpleProcessor = new Definition('Test\SimpleProcessor');
        $simpleProcessor->addTag('processor');

        $abstractProcessor = new Definition();
        $abstractProcessor->setAbstract(true);
        $abstractProcessor->addTag('processor');

        $lazyProcessor = new Definition('Test\LazyProcessor');
        $lazyProcessor->setLazy(true);
        $lazyProcessor->addTag('processor');

        $withArgumentsProcessor = new Definition('Test\WithArgumentsProcessor', ['test']);
        $withArgumentsProcessor->addTag('processor');

        $container->addDefinitions([
            'simple_factory'           => $simpleFactory,
            'simple_processor'         => $simpleProcessor,
            'abstract_processor'       => $abstractProcessor,
            'lazy_processor'           => $lazyProcessor,
            'with_arguments_processor' => $withArgumentsProcessor,
        ]);

        $compilerPass = new CleanUpProcessorsCompilerPass(
            'simple_factory',
            'processor'
        );

        $compilerPass->process($container);

        self::assertFalse($container->hasDefinition('simple_processor'));
        self::assertTrue($container->hasDefinition('abstract_processor'));
        self::assertTrue($container->hasDefinition('lazy_processor'));
        self::assertTrue($container->hasDefinition('with_arguments_processor'));

        self::assertEquals(
            ['simple_processor' => 'Test\SimpleProcessor'],
            $simpleFactory->getArgument(0)
        );
    }

    public function testProcessForSimpleFactoryWithoutArguments()
    {
        $container = new ContainerBuilder();

        $simpleFactory = new Definition(SimpleProcessorFactory::class, []);

        $simpleProcessor = new Definition('Test\SimpleProcessor');
        $simpleProcessor->addTag('processor');

        $container->addDefinitions([
            'simple_factory'   => $simpleFactory,
            'simple_processor' => $simpleProcessor
        ]);

        $compilerPass = new CleanUpProcessorsCompilerPass(
            'simple_factory',
            'processor'
        );

        $compilerPass->process($container);

        self::assertFalse($container->hasDefinition('simple_processor'));

        self::assertEquals(
            ['simple_processor' => 'Test\SimpleProcessor'],
            $simpleFactory->getArgument(0)
        );
    }
}
