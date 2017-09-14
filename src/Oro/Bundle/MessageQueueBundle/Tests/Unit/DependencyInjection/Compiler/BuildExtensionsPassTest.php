<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ChainExtension;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ResettableExtensionWrapper;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildExtensionsPass;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;

class BuildExtensionsPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var BuildExtensionsPass */
    private $buildExtensionsPass;

    protected function setUp()
    {
        $this->buildExtensionsPass = new BuildExtensionsPass();
    }

    public function testShouldReplaceFirstArgumentOfExtensionsServiceConstructorWithTagsExtensions()
    {
        $container = new ContainerBuilder();

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['persistent' => true]);
        $container->setDefinition('foo_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $this->assertEquals(
            [new Reference('foo_extension')],
            $extensions->getArgument(0)
        );
    }

    public function testShouldWrapNotPersistentExtension()
    {
        $container = new ContainerBuilder();

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $extensions);

        $extension = new Definition(AbstractExtension::class);
        $extension->addTag('oro_message_queue.consumption.extension');
        $container->setDefinition('foo_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $this->assertEquals(
            [new Reference('foo_extension.resettable_wrapper')],
            $extensions->getArgument(0)
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

    public function testShouldNotWrapNotPersistentExtensionIfItImplementsResettableExtensionInterface()
    {
        $container = new ContainerBuilder();

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $extensions);

        $extension = new Definition(ChainExtension::class);
        $extension->addTag('oro_message_queue.consumption.extension');
        $container->setDefinition('foo_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $this->assertEquals(
            [new Reference('foo_extension')],
            $extensions->getArgument(0)
        );

        $this->assertFalse($container->hasDefinition('foo_extension.resettable_wrapper'));
    }

    public function testShouldResolveExtensionClassIfItSpecifiedAsParameter()
    {
        $container = new ContainerBuilder();

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $extensions);

        $container->setParameter('foo_extension.class', ChainExtension::class);
        $extension = new Definition('%foo_extension.class%');
        $extension->addTag('oro_message_queue.consumption.extension');
        $container->setDefinition('foo_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $this->assertEquals(
            [new Reference('foo_extension')],
            $extensions->getArgument(0)
        );

        $this->assertFalse($container->hasDefinition('foo_extension.resettable_wrapper'));
    }

    public function testShouldOrderExtensionsByPriority()
    {
        $container = new ContainerBuilder();

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => 6, 'persistent' => true]);
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => -5, 'persistent' => true]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => 2, 'persistent' => true]);
        $container->setDefinition('baz_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $orderedExtensions = $extensions->getArgument(0);

        $this->assertEquals(new Reference('bar_extension'), $orderedExtensions[0]);
        $this->assertEquals(new Reference('baz_extension'), $orderedExtensions[1]);
        $this->assertEquals(new Reference('foo_extension'), $orderedExtensions[2]);
    }

    public function testShouldAssumePriorityZeroIfPriorityIsNotSet()
    {
        $container = new ContainerBuilder();

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['persistent' => true]);
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => 1, 'persistent' => true]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => -1, 'persistent' => true]);
        $container->setDefinition('baz_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $orderedExtensions = $extensions->getArgument(0);

        $this->assertEquals(new Reference('baz_extension'), $orderedExtensions[0]);
        $this->assertEquals(new Reference('foo_extension'), $orderedExtensions[1]);
        $this->assertEquals(new Reference('bar_extension'), $orderedExtensions[2]);
    }
}
