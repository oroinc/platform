<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildTopicMetaRegistryPass;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\DestinationNameTopicSubscriber;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\InvalidTopicSubscriber;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\OnlyTopicNameTopicSubscriber;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BuildTopicMetaRegistryPassTest extends \PHPUnit\Framework\TestCase
{
    private BuildTopicMetaRegistryPass $compiler;

    private ContainerBuilder $container;

    private Definition $registryDefinition;

    protected function setUp(): void
    {
        $this->compiler = new BuildTopicMetaRegistryPass();

        $this->container = new ContainerBuilder();
        $this->registryDefinition = $this->container->register('oro_message_queue.client.meta.topic_meta_registry');
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
                'sample_topic' => [Config::DEFAULT_QUEUE_NAME => 'processor_id'],
            ],
            $this->registryDefinition->getArgument('$messageProcessorsByTopicAndQueue')
        );

        self::assertEquals(
            [
                'sample_topic' => [Config::DEFAULT_QUEUE_NAME],
            ],
            $this->registryDefinition->getArgument('$queuesByTopic')
        );
    }

    public function testProcessWhenDestinationIsSet(): void
    {
        $this->container->register('processor_id', $this->getMockClass(MessageProcessorInterface::class))
            ->addTag(
                'oro_message_queue.client.message_processor',
                ['topicName' => 'sample_topic', 'destinationName' => 'sample_destination']
            );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                'sample_topic' => ['sample_destination' => 'processor_id'],
            ],
            $this->registryDefinition->getArgument('$messageProcessorsByTopicAndQueue')
        );

        self::assertEquals(
            [
                'sample_topic' => ['sample_destination'],
            ],
            $this->registryDefinition->getArgument('$queuesByTopic')
        );
    }

    public function testProcessWhenTopicSubscriberWithTopicName(): void
    {
        $this->container->register('processor_id', OnlyTopicNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor', ['topicName' => 'sample_topic']);

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                'subscribed_topic_name' => [Config::DEFAULT_QUEUE_NAME => 'processor_id'],
            ],
            $this->registryDefinition->getArgument('$messageProcessorsByTopicAndQueue')
        );

        self::assertEquals(
            [
                'subscribed_topic_name' => [Config::DEFAULT_QUEUE_NAME],
            ],
            $this->registryDefinition->getArgument('$queuesByTopic')
        );
    }

    public function testProcessWhenTopicSubscriberWithDestinationName(): void
    {
        $this->container->register('processor_id', DestinationNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                'subscribed_topic_name' => ['subscriber_destination_name' => 'processor_id'],
            ],
            $this->registryDefinition->getArgument('$messageProcessorsByTopicAndQueue')
        );

        self::assertEquals(
            [
                'subscribed_topic_name' => ['subscriber_destination_name'],
            ],
            $this->registryDefinition->getArgument('$queuesByTopic')
        );
    }

    public function testProcessWhenInvalidTopic(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic subscriber configuration is invalid. "[12345]"');

        $container = new ContainerBuilder();
        $container->register('oro_message_queue.client.meta.topic_meta_registry')
            ->addArgument([]);

        $container->register('processor_id', InvalidTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);
    }
}
