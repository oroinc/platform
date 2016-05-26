<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildTopicMetaSubscribersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildTopicMetaSubscribersPassTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new BuildTopicMetaSubscribersPass();
    }

    public function testShouldBuildTopicMetaSubscribersForOneTagAndEmptyRegistry()
    {
        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor', [
            'topicName' => 'topic',
            'processorName' => 'processor-name',
        ]);
        $container->setDefinition('processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[]]);
        $container->setDefinition('oro_message_queue.zero_config.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);

        $expectedValue = [
            'topic' => ['subscribers' => ['processor-name']],
        ];

        $this->assertEquals($expectedValue, $topicMetaRegistry->getArgument(0));
    }

    public function testShouldBuildTopicMetaSubscribersForOneTagAndSameMetaInRegistry()
    {
        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor', [
            'topicName' => 'topic',
            'processorName' => 'barProcessorName',
        ]);
        $container->setDefinition('processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[
            'topic' => ['description' => 'aDescription', 'subscribers' => ['fooProcessorName']],
        ]]);
        $container->setDefinition('oro_message_queue.zero_config.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);

        $expectedValue = [
            'topic' => [
                'description' => 'aDescription',
                'subscribers' => ['fooProcessorName', 'barProcessorName',]
            ],
        ];

        $this->assertEquals($expectedValue, $topicMetaRegistry->getArgument(0));
    }

    public function testShouldBuildTopicMetaSubscribersForOneTagAndSameMetaInPlusAnotherRegistry()
    {
        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor', [
            'topicName' => 'fooTopic',
            'processorName' => 'barProcessorName',
        ]);
        $container->setDefinition('processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[
            'fooTopic' => ['description' => 'aDescription', 'subscribers' => ['fooProcessorName']],
            'barTopic' => ['description' => 'aBarDescription'],
        ]]);
        $container->setDefinition('oro_message_queue.zero_config.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);

        $expectedValue = [
            'fooTopic' => [
                'description' => 'aDescription',
                'subscribers' => ['fooProcessorName', 'barProcessorName',]
            ],
            'barTopic' => ['description' => 'aBarDescription'],
        ];

        $this->assertEquals($expectedValue, $topicMetaRegistry->getArgument(0));
    }

    public function testShouldBuildTopicMetaSubscribersForTwoTagAndEmptyRegistry()
    {
        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor', [
            'topicName' => 'fooTopic',
            'processorName' => 'fooProcessorName',
        ]);
        $container->setDefinition('processor-id', $processor);

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor', [
            'topicName' => 'fooTopic',
            'processorName' => 'barProcessorName',
        ]);
        $container->setDefinition('another-processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[]]);
        $container->setDefinition('oro_message_queue.zero_config.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);

        $expectedValue = [
            'fooTopic' => [
                'subscribers' => ['fooProcessorName', 'barProcessorName',]
            ],
        ];

        $this->assertEquals($expectedValue, $topicMetaRegistry->getArgument(0));
    }

    public function testShouldBuildTopicMetaSubscribersForTwoTagSameMetaRegistry()
    {
        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor', [
            'topicName' => 'fooTopic',
            'processorName' => 'fooProcessorName',
        ]);
        $container->setDefinition('processor-id', $processor);

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor', [
            'topicName' => 'fooTopic',
            'processorName' => 'barProcessorName',
        ]);
        $container->setDefinition('another-processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[
            'fooTopic' => ['description' => 'aDescription', 'subscribers' => ['bazProcessorName']],
        ]]);
        $container->setDefinition('oro_message_queue.zero_config.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);

        $expectedValue = [
            'fooTopic' => [
                'description' => 'aDescription',
                'subscribers' => ['bazProcessorName', 'fooProcessorName', 'barProcessorName',]
            ],
        ];

        $this->assertEquals($expectedValue, $topicMetaRegistry->getArgument(0));
    }

    public function testShouldSkipServiceWithEmptyTopicNameAttribute()
    {
        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor');
        $container->setDefinition('processor', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[]]);
        $container->setDefinition('oro_message_queue.zero_config.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);
    }

    public function testShouldSetServiceIdAdProcessorIdIfIsNotSetInTag()
    {
        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor', [
            'topicName' => 'topic',
        ]);
        $container->setDefinition('processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[]]);
        $container->setDefinition('oro_message_queue.zero_config.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);

        $expectedValue = [
            'topic' => ['subscribers' => ['processor-id']],
        ];

        $this->assertEquals($expectedValue, $topicMetaRegistry->getArgument(0));
    }
}
