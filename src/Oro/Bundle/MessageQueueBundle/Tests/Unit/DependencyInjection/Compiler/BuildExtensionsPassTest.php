<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ChainExtension;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ResettableExtensionWrapper;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildExtensionsPass;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BuildExtensionsPassTest extends \PHPUnit\Framework\TestCase
{
    private BuildExtensionsPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new BuildExtensionsPass();
    }

    public function testConsumptionShouldReplaceFirstArgumentOfExtensionsServiceConstructorWithTagsExtensions()
    {
        $container = new ContainerBuilder();
        $consumptionExtensionsDef = $container->register('oro_message_queue.consumption.extensions')
            ->addArgument([]);

        $container->register('foo_extension')
            ->addTag('oro_message_queue.consumption.extension', ['persistent' => true]);

        $this->compiler->process($container);

        $this->assertEquals(
            [new Reference('foo_extension')],
            $consumptionExtensionsDef->getArgument(0)
        );
    }

    public function testConsumptionShouldWrapNotPersistentExtension()
    {
        $container = new ContainerBuilder();
        $consumptionExtensionsDef = $container->register('oro_message_queue.consumption.extensions')
            ->addArgument([]);

        $container->register('foo_extension', AbstractExtension::class)
            ->addTag('oro_message_queue.consumption.extension');

        $this->compiler->process($container);

        $this->assertEquals(
            [new Reference('foo_extension.resettable_wrapper')],
            $consumptionExtensionsDef->getArgument(0)
        );

        $this->assertTrue($container->hasDefinition('foo_extension.resettable_wrapper'));
        $wrapper = $container->getDefinition('foo_extension.resettable_wrapper');
        $this->assertEquals(ResettableExtensionWrapper::class, $wrapper->getClass());
        $this->assertFalse($wrapper->isPublic());
        $this->assertEquals(
            [new Reference('service_container'), 'foo_extension'],
            $wrapper->getArguments()
        );
    }

    public function testConsumptionShouldNotWrapNotPersistentExtensionIfItImplementsResettableExtensionInterface()
    {
        $container = new ContainerBuilder();
        $consumptionExtensionsDef = $container->register('oro_message_queue.consumption.extensions')
            ->addArgument([]);

        $container->register('foo_extension', ChainExtension::class)
            ->addTag('oro_message_queue.consumption.extension');

        $this->compiler->process($container);

        $this->assertEquals(
            [new Reference('foo_extension')],
            $consumptionExtensionsDef->getArgument(0)
        );

        $this->assertFalse($container->hasDefinition('foo_extension.resettable_wrapper'));
    }

    public function testConsumptionShouldResolveExtensionClassIfItSpecifiedAsParameter()
    {
        $container = new ContainerBuilder();
        $consumptionExtensionsDef = $container->register('oro_message_queue.consumption.extensions')
            ->addArgument([]);

        $container->setParameter('foo_extension.class', ChainExtension::class);
        $container->register('foo_extension', '%foo_extension.class%')
            ->addTag('oro_message_queue.consumption.extension');

        $this->compiler->process($container);

        $this->assertEquals(
            [new Reference('foo_extension')],
            $consumptionExtensionsDef->getArgument(0)
        );

        $this->assertFalse($container->hasDefinition('foo_extension.resettable_wrapper'));
    }

    public function testConsumptionShouldOrderExtensionsByPriority()
    {
        $container = new ContainerBuilder();
        $consumptionExtensionsDef = $container->register('oro_message_queue.consumption.extensions')
            ->addArgument([]);

        $container->register('foo_extension')
            ->addTag('oro_message_queue.consumption.extension', ['priority' => -6, 'persistent' => true]);
        $container->register('bar_extension')
            ->addTag('oro_message_queue.consumption.extension', ['priority' => 5, 'persistent' => true]);
        $container->register('baz_extension')
            ->addTag('oro_message_queue.consumption.extension', ['priority' => -2, 'persistent' => true]);

        $this->compiler->process($container);

        $this->assertEquals(
            [
                new Reference('bar_extension'),
                new Reference('baz_extension'),
                new Reference('foo_extension')
            ],
            $consumptionExtensionsDef->getArgument(0)
        );
    }

    public function testConsumptionShouldAssumePriorityZeroIfPriorityIsNotSet()
    {
        $container = new ContainerBuilder();
        $consumptionExtensionsDef = $container->register('oro_message_queue.consumption.extensions')
            ->addArgument([]);

        $container->register('foo_extension')
            ->addTag('oro_message_queue.consumption.extension', ['persistent' => true]);
        $container->register('bar_extension')
            ->addTag('oro_message_queue.consumption.extension', ['priority' => -1, 'persistent' => true]);
        $container->register('baz_extension')
            ->addTag('oro_message_queue.consumption.extension', ['priority' => 1, 'persistent' => true]);

        $this->compiler->process($container);

        $this->assertEquals(
            [
                new Reference('baz_extension'),
                new Reference('foo_extension'),
                new Reference('bar_extension')
            ],
            $consumptionExtensionsDef->getArgument(0)
        );
    }
}
