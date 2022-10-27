<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildMessageProcessorRegistryPass;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BuildMessageProcessorRegistryPassTest extends \PHPUnit\Framework\TestCase
{
    private BuildMessageProcessorRegistryPass $compiler;

    private ContainerBuilder $container;

    private Definition $registryDefinition;

    protected function setUp(): void
    {
        $this->compiler = new BuildMessageProcessorRegistryPass();

        $this->container = new ContainerBuilder();
        $this->registryDefinition = $this->container->register('oro_message_queue.client.message_processor_registry')
            ->addArgument([]);
    }

    public function testWhenRegistryIsNotDefined(): void
    {
        $container = new ContainerBuilder();

        $container->register('processor_id', $this->getMockClass(MessageProcessorInterface::class))
            ->addTag('oro_message_queue.client.message_processor', []);

        $this->compiler->process($container);
    }

    public function testProcess(): void
    {
        $this->container->register('processor_id')
            ->addTag(
                'oro_message_queue.client.message_processor',
                ['topicName' => 'topic']
            );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                new Reference('processor_id'),
                new Reference('oro_message_queue.client.noop_message_processor'),
            ],
            $this->registryDefinition->getArgument(0)
        );
    }
}
