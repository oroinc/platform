<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildExtensionsPass;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BuildExtensionsPassTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementCompilerPass()
    {
        $this->assertClassImplements(CompilerPassInterface::class, BuildExtensionsPass::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new BuildExtensionsPass();
    }

    public function testShouldReplaceFirstArgumentOfExtensionsServiceConstructorWithTaggsExtensions()
    {
        $container = new ContainerBuilder();

        $consumptionExtensions = new Definition();
        $consumptionExtensions->addArgument([]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $consumptionExtensions);

        $consumptionExtension = new Definition();
        $consumptionExtension->addTag('oro_message_queue.consumption.extension');
        $container->setDefinition('foo_extension', $consumptionExtension);

        $consumptionExtension = new Definition();
        $consumptionExtension->addTag('oro_message_queue.consumption.extension');
        $container->setDefinition('bar_extension', $consumptionExtension);

        $jobExtensions = new Definition();
        $jobExtensions->addArgument([]);
        $container->setDefinition('oro_message_queue.job.extensions', $jobExtensions);

        $jobExtension = new Definition();
        $jobExtension->addTag('oro_message_queue.job.extension');
        $container->setDefinition('foo_job_extension', $jobExtension);

        $jobExtension = new Definition();
        $jobExtension->addTag('oro_message_queue.job.extension');
        $container->setDefinition('bar_job_extension', $jobExtension);

        $pass = new BuildExtensionsPass();
        $pass->process($container);

        $this->assertEquals(
            [new Reference('foo_extension'), new Reference('bar_extension')],
            $consumptionExtensions->getArgument(0)
        );
        $this->assertEquals(
            [new Reference('foo_job_extension'), new Reference('bar_job_extension')],
            $jobExtensions->getArgument(0)
        );
    }

    public function testShouldOrderExtensionsByPriority()
    {
        $container = new ContainerBuilder();

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => 6]);
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => -5]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => 2]);
        $container->setDefinition('baz_extension', $extension);

        $jobExtensions = new Definition();
        $jobExtensions->addArgument([]);
        $container->setDefinition('oro_message_queue.job.extensions', $jobExtensions);

        $jobExtension = new Definition();
        $jobExtension->addTag('oro_message_queue.job.extension');
        $container->setDefinition('foo_job_extension', $jobExtension);

        $pass = new BuildExtensionsPass();
        $pass->process($container);

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
        $extension->addTag('oro_message_queue.consumption.extension');
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => 1]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension', ['priority' => -1]);
        $container->setDefinition('baz_extension', $extension);

        $jobExtensions = new Definition();
        $jobExtensions->addArgument([]);
        $container->setDefinition('oro_message_queue.job.extensions', $jobExtensions);

        $jobExtension = new Definition();
        $jobExtension->addTag('oro_message_queue.job.extension');
        $container->setDefinition('foo_job_extension', $jobExtension);

        $pass = new BuildExtensionsPass();
        $pass->process($container);

        $orderedExtensions = $extensions->getArgument(0);

        $this->assertEquals(new Reference('baz_extension'), $orderedExtensions[0]);
        $this->assertEquals(new Reference('foo_extension'), $orderedExtensions[1]);
        $this->assertEquals(new Reference('bar_extension'), $orderedExtensions[2]);
    }
}
