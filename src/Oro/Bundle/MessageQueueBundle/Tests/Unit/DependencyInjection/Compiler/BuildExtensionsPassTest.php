<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\DependencyInjection\Compiler;

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

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('oro_message_queue.consumption.extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension');
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('oro_message_queue.consumption.extension');
        $container->setDefinition('bar_extension', $extension);


        $pass = new BuildExtensionsPass();
        $pass->process($container);

        $this->assertEquals(
            [new Reference('foo_extension'), new Reference('bar_extension')],
            $extensions->getArgument(0)
        );
    }
}
