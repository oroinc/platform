<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ChainExtension;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ResettableExtensionWrapper;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildExtensionsPass;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BuildExtensionsPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var BuildExtensionsPass */
    private $buildExtensionsPass;

    protected function setUp()
    {
        $this->buildExtensionsPass = new BuildExtensionsPass();
    }

    public function testConsumptionShouldReplaceFirstArgumentOfExtensionsServiceConstructorWithTagsExtensions()
    {
        $container = new ContainerBuilder();

        $consumptionExtensions = new Definition('ConsumptionExtension', [[]]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $consumptionExtensions);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['persistent' => true]);
        $container->setDefinition('foo_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $this->assertEquals(
            [new Reference('foo_extension')],
            $consumptionExtensions->getArgument(0)
        );
    }

    public function testConsumptionShouldWrapNotPersistentExtension()
    {
        $container = new ContainerBuilder();

        $consumptionExtensions = new Definition('ConsumptionExtension', [[]]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $consumptionExtensions);

        $extension = new Definition(AbstractExtension::class);
        $extension->addTag('oro_message_queue.consumption.extension');
        $container->setDefinition('foo_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $this->assertEquals(
            [new Reference('foo_extension.resettable_wrapper')],
            $consumptionExtensions->getArgument(0)
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

        $consumptionExtensions = new Definition('ConsumptionExtension', [[]]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $consumptionExtensions);

        $extension = new Definition(ChainExtension::class);
        $extension->addTag('oro_message_queue.consumption.extension');
        $container->setDefinition('foo_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $this->assertEquals(
            [new Reference('foo_extension')],
            $consumptionExtensions->getArgument(0)
        );

        $this->assertFalse($container->hasDefinition('foo_extension.resettable_wrapper'));
    }

    public function testConsumptionShouldResolveExtensionClassIfItSpecifiedAsParameter()
    {
        $container = new ContainerBuilder();

        $consumptionExtensions = new Definition('ConsumptionExtension', [[]]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $consumptionExtensions);

        $container->setParameter('foo_extension.class', ChainExtension::class);
        $extension = new Definition('%foo_extension.class%');
        $extension->addTag('oro_message_queue.consumption.extension');
        $container->setDefinition('foo_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $this->assertEquals(
            [new Reference('foo_extension')],
            $consumptionExtensions->getArgument(0)
        );

        $this->assertFalse($container->hasDefinition('foo_extension.resettable_wrapper'));
    }

    public function testConsumptionShouldOrderExtensionsByPriority()
    {
        $container = new ContainerBuilder();

        $consumptionExtensions = new Definition('ConsumptionExtension', [[]]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $consumptionExtensions);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => -6, 'persistent' => true]);
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => 5, 'persistent' => true]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => -2, 'persistent' => true]);
        $container->setDefinition('baz_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $orderedExtensions = $consumptionExtensions->getArgument(0);

        $this->assertEquals(new Reference('bar_extension'), $orderedExtensions[0]);
        $this->assertEquals(new Reference('baz_extension'), $orderedExtensions[1]);
        $this->assertEquals(new Reference('foo_extension'), $orderedExtensions[2]);
    }

    public function testConsumptionShouldAssumePriorityZeroIfPriorityIsNotSet()
    {
        $container = new ContainerBuilder();

        $consumptionExtensions = new Definition('ConsumptionExtension', [[]]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $consumptionExtensions);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['persistent' => true]);
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => -1, 'persistent' => true]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => 1, 'persistent' => true]);
        $container->setDefinition('baz_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $orderedExtensions = $consumptionExtensions->getArgument(0);

        $this->assertEquals(new Reference('baz_extension'), $orderedExtensions[0]);
        $this->assertEquals(new Reference('foo_extension'), $orderedExtensions[1]);
        $this->assertEquals(new Reference('bar_extension'), $orderedExtensions[2]);
    }

    public function testJobShouldReplaceFirstArgumentOfExtensionsServiceConstructorWithTagsExtensions()
    {
        $container = new ContainerBuilder();

        $jobExtensions = new Definition('JobExtension', [[]]);
        $container->setDefinition('oro_message_queue.job.extensions', $jobExtensions);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.job.extension', []);
        $container->setDefinition('foo_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $this->assertEquals(
            [new Reference('foo_extension')],
            $jobExtensions->getArgument(0)
        );
    }

    public function testJobShouldOrderExtensionsByPriority()
    {
        $container = new ContainerBuilder();

        $jobExtensions = new Definition('JobExtension', [[]]);
        $container->setDefinition('oro_message_queue.job.extensions', $jobExtensions);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.job.extension', ['priority' => -6]);
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.job.extension', ['priority' => 5]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.job.extension', ['priority' => -2]);
        $container->setDefinition('baz_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $orderedExtensions = $jobExtensions->getArgument(0);

        $this->assertEquals(new Reference('bar_extension'), $orderedExtensions[0]);
        $this->assertEquals(new Reference('baz_extension'), $orderedExtensions[1]);
        $this->assertEquals(new Reference('foo_extension'), $orderedExtensions[2]);
    }

    public function testJobShouldAssumePriorityZeroIfPriorityIsNotSet()
    {
        $container = new ContainerBuilder();

        $jobExtensions = new Definition('JobExtension', [[]]);
        $container->setDefinition('oro_message_queue.job.extensions', $jobExtensions);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.job.extension', []);
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.job.extension', ['priority' => -1]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.job.extension', ['priority' => 1]);
        $container->setDefinition('baz_extension', $extension);

        $this->buildExtensionsPass->process($container);

        $orderedExtensions = $jobExtensions->getArgument(0);

        $this->assertEquals(new Reference('baz_extension'), $orderedExtensions[0]);
        $this->assertEquals(new Reference('foo_extension'), $orderedExtensions[1]);
        $this->assertEquals(new Reference('bar_extension'), $orderedExtensions[2]);
    }
}
