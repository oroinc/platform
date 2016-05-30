<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildMessageProcessorRegistryPass;
use Oro\Component\MessageQueue\ZeroConfig\TopicSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildMessageProcessorRegistryPassTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new BuildMessageProcessorRegistryPass();
    }

    public function testShouldBuildRouteRegistry()
    {
        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor', [
            'topicName' => 'topic',
            'processorName' => 'processor-name',
        ]);
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('oro_message_queue.zero_config.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();
        $pass->process($container);

        $expectedValue = [
            'processor-name' => 'processor-id',
        ];

        $this->assertEquals($expectedValue, $processorRegistry->getArgument(0));
    }

    public function testShouldThrowExceptionIfTopicNameIsNotSet()
    {
        $this->setExpectedException(
            \LogicException::class,
            'Topic name is not set but it is required. service: "processor",'.
            ' tag: "oro_message_queue.zero_config.message'
        );

        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor');
        $container->setDefinition('processor', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('oro_message_queue.zero_config.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();
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

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('oro_message_queue.zero_config.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();
        $pass->process($container);

        $expectedValue = [
            'processor-id' => 'processor-id',
        ];

        $this->assertEquals($expectedValue, $processorRegistry->getArgument(0));
    }

    public function testShouldBuildRouteFromSubscriberIfOnlyTopicNameSpecified()
    {
        $container = new ContainerBuilder();

        $processor = new Definition(OnlyTopicNameTopicSubscriber::class);
        $processor->addTag('oro_message_queue.zero_config.message_processor');
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('oro_message_queue.zero_config.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();
        $pass->process($container);

        $expectedValue = [
            'processor-id' => 'processor-id',
        ];

        $this->assertEquals($expectedValue, $processorRegistry->getArgument(0));
    }

    public function testShouldBuildRouteFromSubscriberIfProcessorNameSpecified()
    {
        $container = new ContainerBuilder();

        $processor = new Definition(ProcessorNameTopicSubscriber::class);
        $processor->addTag('oro_message_queue.zero_config.message_processor');
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('oro_message_queue.zero_config.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();
        $pass->process($container);

        $expectedValue = [
            'subscriber-processor-name' => 'processor-id',
        ];

        $this->assertEquals($expectedValue, $processorRegistry->getArgument(0));
    }

    public function testShouldThrowExceptionWhenTopicSubscriberConfigurationIsInvalid()
    {
        $this->setExpectedException(\LogicException::class, 'Topic subscriber configuration is invalid. "[12345]"');

        $container = new ContainerBuilder();

        $processor = new Definition(InvalidTopicSubscriber::class);
        $processor->addTag('oro_message_queue.zero_config.message_processor');
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('oro_message_queue.zero_config.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();
        $pass->process($container);
    }
}

// @codingStandardsIgnoreStart

class OnlyTopicNameTopicSubscriber implements TopicSubscriber
{
    public static function getSubscribedTopics()
    {
        return ['topic-subscriber-name'];
    }
}

class ProcessorNameTopicSubscriber implements TopicSubscriber
{
    public static function getSubscribedTopics()
    {
        return [
            'topic-subscriber-name' => [
                'processorName' => 'subscriber-processor-name'
            ],
        ];
    }
}

class InvalidTopicSubscriber implements TopicSubscriber
{
    public static function getSubscribedTopics()
    {
        return [12345];
    }
}

// @codingStandardsIgnoreEnd