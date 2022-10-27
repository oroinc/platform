<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildDestinationMetaRegistryPass;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\DestinationNameTopicSubscriber;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\OnlyTopicNameTopicSubscriber;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildDestinationMetaRegistryPassTest extends \PHPUnit\Framework\TestCase
{
    private const REGISTRY_ID = 'oro_message_queue.client.meta.destination_meta_registry';

    private BuildDestinationMetaRegistryPass $compiler;

    private ContainerBuilder $container;

    private Definition $registryDefinition;

    protected function setUp(): void
    {
        $this->compiler = new BuildDestinationMetaRegistryPass();

        $this->container = new ContainerBuilder();
        $this->registryDefinition = $this->container->register(self::REGISTRY_ID);
    }

    public function testWhenRegistryIsNotDefined(): void
    {
        $container = new ContainerBuilder();

        $container->register('processor_id', $this->getMockClass(MessageProcessorInterface::class))
            ->addTag('oro_message_queue.client.message_processor', []);

        $this->compiler->process($container);
    }

    public function testProcessWhenTopicIsNotSet(): void
    {
        $this->container->register('processor_id', $this->getMockClass(MessageProcessorInterface::class))
            ->addTag('oro_message_queue.client.message_processor');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Attribute "topicName" of tag "oro_message_queue.client.message_processor" '
            . 'was expected to be set on service "processor_id"'
        );

        $this->compiler->process($this->container);
    }

    public function testProcessWhenTopicIsSet(): void
    {
        $this->container->register('processor_id', $this->getMockClass(MessageProcessorInterface::class))
            ->addTag('oro_message_queue.client.message_processor', ['topicName' => 'sample_topic']);

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                Config::DEFAULT_QUEUE_NAME => ['processor_id'],
            ],
            $this->registryDefinition->getArgument('$messageProcessorsByQueue')
        );
    }

    public function testProcessWhenDestinationIsSet(): void
    {
        $this->container->register('processor_id', $this->getMockClass(MessageProcessorInterface::class))
            ->addTag(
                'oro_message_queue.client.message_processor',
                ['topicName' => 'topic_name', 'destinationName' => 'sample_destination']
            );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                'sample_destination' => ['processor_id'],
            ],
            $this->registryDefinition->getArgument('$messageProcessorsByQueue')
        );
    }

    public function testProcessWhenTopicSubscriberWithTopicName(): void
    {
        $this->container->register('processor_id', OnlyTopicNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor', ['topicName' => 'sample_topic']);

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                Config::DEFAULT_QUEUE_NAME => ['processor_id'],
            ],
            $this->registryDefinition->getArgument('$messageProcessorsByQueue')
        );
    }

    public function testProcessWhenTopicSubscriberWithDestinationName(): void
    {
        $this->container->register('processor_id', DestinationNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                'subscriber_destination_name' => ['processor_id'],
            ],
            $this->registryDefinition->getArgument('$messageProcessorsByQueue')
        );
    }
}
