<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildTopicMetaSubscribersPass;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\InvalidTopicSubscriber;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\OnlyTopicNameTopicSubscriber;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\ProcessorNameTopicSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BuildTopicMetaSubscribersPassTest extends \PHPUnit\Framework\TestCase
{
    private BuildTopicMetaSubscribersPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new BuildTopicMetaSubscribersPass();
    }

    public function testShouldBuildTopicMetaSubscribersForOneTagAndEmptyRegistry()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.topic_meta_registry')
            ->addArgument([]);

        $container->register('processor_id')
            ->addTag(
                'oro_message_queue.client.message_processor',
                ['topicName' => 'topic', 'processorName' => 'processor-name']
            );

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'topic' => ['subscribers' => ['processor-name']]
            ],
            $registryDef->getArgument(0)
        );
    }

    public function testShouldBuildTopicMetaSubscribersForOneTagAndSameMetaInRegistry()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.topic_meta_registry')
            ->addArgument(['topic' => ['description' => 'aDescription', 'subscribers' => ['fooProcessorName']]]);

        $container->register('processor_id')
            ->addTag(
                'oro_message_queue.client.message_processor',
                ['topicName' => 'topic', 'processorName' => 'barProcessorName']
            );

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'topic' => [
                    'description' => 'aDescription',
                    'subscribers' => ['fooProcessorName', 'barProcessorName']
                ]
            ],
            $registryDef->getArgument(0)
        );
    }

    public function testShouldBuildTopicMetaSubscribersForOneTagAndSameMetaInPlusAnotherRegistry()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.topic_meta_registry')
            ->addArgument(
                [
                    'fooTopic' => ['description' => 'aDescription', 'subscribers' => ['fooProcessorName']],
                    'barTopic' => ['description' => 'aBarDescription']
                ]
            );

        $container->register('processor_id')
            ->addTag(
                'oro_message_queue.client.message_processor',
                ['topicName' => 'fooTopic', 'processorName' => 'barProcessorName']
            );

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'fooTopic' => [
                    'description' => 'aDescription',
                    'subscribers' => ['fooProcessorName', 'barProcessorName']
                ],
                'barTopic' => ['description' => 'aBarDescription']
            ],
            $registryDef->getArgument(0)
        );
    }

    public function testShouldBuildTopicMetaSubscribersForTwoTagAndEmptyRegistry()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.topic_meta_registry')
            ->addArgument([]);

        $container->register('processor_id')
            ->addTag(
                'oro_message_queue.client.message_processor',
                ['topicName' => 'fooTopic', 'processorName' => 'fooProcessorName']
            );
        $container->register('another_processor_id')
            ->addTag(
                'oro_message_queue.client.message_processor',
                ['topicName' => 'fooTopic', 'processorName' => 'barProcessorName']
            );

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'fooTopic' => [
                    'subscribers' => ['fooProcessorName', 'barProcessorName']
                ]
            ],
            $registryDef->getArgument(0)
        );
    }

    public function testShouldBuildTopicMetaSubscribersForTwoTagSameMetaRegistry()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.topic_meta_registry')
            ->addArgument(['fooTopic' => ['description' => 'aDescription', 'subscribers' => ['bazProcessorName']]]);

        $container->register('processor_id')
            ->addTag(
                'oro_message_queue.client.message_processor',
                ['topicName' => 'fooTopic', 'processorName' => 'fooProcessorName']
            );
        $container->register('another_processor_id')
            ->addTag(
                'oro_message_queue.client.message_processor',
                ['topicName' => 'fooTopic', 'processorName' => 'barProcessorName']
            );

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'fooTopic' => [
                    'description' => 'aDescription',
                    'subscribers' => ['bazProcessorName', 'fooProcessorName', 'barProcessorName']
                ]
            ],
            $registryDef->getArgument(0)
        );
    }

    public function testShouldSkipServiceWithEmptyTopicNameAttribute()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.topic_meta_registry')
            ->addArgument([]);

        $container->register('processor_id')
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);

        $this->assertEquals([], $registryDef->getArgument(0));
    }

    public function testShouldSetServiceIdAdProcessorIdIfIsNotSetInTag()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.topic_meta_registry')
            ->addArgument([]);

        $container->register('processor_id')
            ->addTag('oro_message_queue.client.message_processor', ['topicName' => 'topic']);

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'topic' => ['subscribers' => ['processor_id']]
            ],
            $registryDef->getArgument(0)
        );
    }

    public function testShouldBuildMetaFromSubscriberIfOnlyTopicNameSpecified()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.topic_meta_registry')
            ->addArgument([]);

        $container->register('processor_id', OnlyTopicNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'topic-subscriber-name' => ['subscribers' => ['processor_id']]
            ],
            $registryDef->getArgument(0)
        );
    }

    public function testShouldBuildMetaFromSubscriberIfProcessorNameSpecified()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.topic_meta_registry')
            ->addArgument([]);

        $container->register('processor_id', ProcessorNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'topic-subscriber-name' => ['subscribers' => ['subscriber-processor-name']]
            ],
            $registryDef->getArgument(0)
        );
    }

    public function testShouldThrowExceptionWhenTopicSubscriberConfigurationIsInvalid()
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
