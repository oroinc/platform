<?php

namespace Oro\Component\ChainProcessor\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

use Oro\Component\ChainProcessor\DependencyInjection\CleanUpProcessorsCompilerPass;

class CleanUpProcessorsCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessWithoutSimpleFactory()
    {
        $container = new ContainerBuilder();

        $simpleProcessor = new Definition('Test\SimpleProcessor');
        $simpleProcessor->addTag('processor');

        $container->addDefinitions(
            [
                'simple_processor' => $simpleProcessor,
            ]
        );

        $compilerPass = new CleanUpProcessorsCompilerPass(
            'simple_factory',
            'processor'
        );

        $compilerPass->process($container);

        $this->assertTrue($container->hasDefinition('simple_processor'));
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();

        $simpleFactory = new Definition();

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

        $container->addDefinitions(
            [
                'simple_factory'           => $simpleFactory,
                'simple_processor'         => $simpleProcessor,
                'abstract_processor'       => $abstractProcessor,
                'lazy_processor'           => $lazyProcessor,
                'with_arguments_processor' => $withArgumentsProcessor,
            ]
        );

        $compilerPass = new CleanUpProcessorsCompilerPass(
            'simple_factory',
            'processor'
        );

        $compilerPass->process($container);

        $this->assertFalse($container->hasDefinition('simple_processor'));
        $this->assertTrue($container->hasDefinition('abstract_processor'));
        $this->assertTrue($container->hasDefinition('lazy_processor'));
        $this->assertTrue($container->hasDefinition('with_arguments_processor'));

        $methodCalls = $simpleFactory->getMethodCalls();
        $this->assertCount(1, $methodCalls);
        $this->assertEquals(
            [
                'addProcessor',
                ['simple_processor', 'Test\SimpleProcessor']
            ],
            $methodCalls[0]
        );
    }
}
