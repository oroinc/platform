<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildMessageProcessorRegistryPass;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\InvalidTopicSubscriber;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\OnlyTopicNameTopicSubscriber;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\ProcessorNameTopicSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildMessageProcessorRegistryPassTest extends \PHPUnit\Framework\TestCase
{
    private BuildMessageProcessorRegistryPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new BuildMessageProcessorRegistryPass();
    }

    public function testShouldBuildRouteRegistry()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.message_processor_registry')
            ->addArgument([]);

        $container->register('processor_id')
            ->addTag(
                'oro_message_queue.client.message_processor',
                ['topicName' => 'topic', 'processorName' => 'processor-name']
            );

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'processor-name' => 'processor_id'
            ],
            $registryDef->getArgument(0)
        );
    }

    public function testShouldThrowExceptionIfTopicNameIsNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Topic name is not set but it is required. service: "processor_id",'.
            ' tag: "oro_message_queue.client.message'
        );

        $container = new ContainerBuilder();
        $container->register('oro_message_queue.client.message_processor_registry')
            ->addArgument([]);

        $container->register('processor_id')
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);
    }

    public function testShouldSetServiceIdAdProcessorIdIfIsNotSetInTag()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.message_processor_registry')
            ->addArgument([]);

        $container->register('processor_id')
            ->addTag('oro_message_queue.client.message_processor', ['topicName' => 'topic']);

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'processor_id' => 'processor_id'
            ],
            $registryDef->getArgument(0)
        );
    }

    public function testShouldBuildRouteFromSubscriberIfOnlyTopicNameSpecified()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.message_processor_registry')
            ->addArgument([]);

        $container->register('processor_id', OnlyTopicNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'processor_id' => 'processor_id'
            ],
            $registryDef->getArgument(0)
        );
    }

    public function testShouldBuildRouteFromSubscriberIfProcessorNameSpecified()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.message_processor_registry')
            ->addArgument([]);

        $container->register('processor_id', ProcessorNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'subscriber-processor-name' => 'processor_id'
            ],
            $registryDef->getArgument(0)
        );
    }

    public function testShouldThrowExceptionWhenTopicSubscriberConfigurationIsInvalid()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic subscriber configuration is invalid. "[12345]"');

        $container = new ContainerBuilder();
        $container->register('oro_message_queue.client.message_processor_registry')
            ->addArgument([]);

        $container->register('processor_id', InvalidTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);
    }
}
