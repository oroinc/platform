<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\RegisterTopicsPass;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RegisterTopicsPassTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementCompilerPass()
    {
        $this->assertClassImplements(CompilerPassInterface::class, RegisterTopicsPass::class);
    }

    public function testCouldBeConstructedWithTopicsArrayAsFirstArgument()
    {
        new RegisterTopicsPass([]);
    }

    public function testShouldAddTopicsInRegistryKeepingPreviouslyAdded()
    {
        $container = new ContainerBuilder();

        $registry = new Definition(null, [[
            'bazTopic' => []
        ]]);
        $container->setDefinition('oro_message_queue.zero_config.topic_registry', $registry);

        $pass = new RegisterTopicsPass(['fooTopic' => [], 'barTopic' => []]);
        $pass->process($container);

        $expectedTopics = [
            'bazTopic' => [],
            'fooTopic' => [],
            'barTopic' => [],
        ];

        $this->assertSame($expectedTopics, $registry->getArgument(0));
    }
}
