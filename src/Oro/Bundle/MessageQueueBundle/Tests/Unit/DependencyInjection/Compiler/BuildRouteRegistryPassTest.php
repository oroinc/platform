<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildRouteRegistryPass;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\DestinationNameTopicSubscriber;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\InvalidTopicSubscriber;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\OnlyTopicNameTopicSubscriber;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\ProcessorNameTopicSubscriber;
use Oro\Component\MessageQueue\Client\Config;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildRouteRegistryPassTest extends \PHPUnit\Framework\TestCase
{
    private BuildRouteRegistryPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new BuildRouteRegistryPass();
    }

    public function testShouldBuildRouteRegistry()
    {
        $container = new ContainerBuilder();
        $routerDef = $container->register('oro_message_queue.client.router')
            ->setArguments([null, null, null]);

        $container->register('processor_id')
            ->addTag(
                'oro_message_queue.client.message_processor',
                ['topicName' => 'topic', 'processorName' => 'processor-name', 'destinationName' => 'destination']
            );

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'topic' =>  [
                    ['processor-name', 'destination']
                ]
            ],
            $routerDef->getArgument(2)
        );
    }

    public function testShouldThrowExceptionIfTopicNameIsNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Topic name is not set but it is required. service: "processor_id", '.
            'tag: "oro_message_queue.client.message'
        );

        $container = new ContainerBuilder();
        $container->register('oro_message_queue.client.router')
            ->setArguments([null, null, null]);

        $container->register('processor_id')
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);
    }

    public function testShouldSetServiceIdAdProcessorIdIfIsNotSetInTag()
    {
        $container = new ContainerBuilder();
        $routerDef = $container->register('oro_message_queue.client.router')
            ->setArguments([null, null, null]);

        $container->register('processor_id')
            ->addTag(
                'oro_message_queue.client.message_processor',
                ['topicName' => 'topic', 'destinationName' => 'destination']
            );

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'topic' =>  [
                    ['processor_id', 'destination']
                ]
            ],
            $routerDef->getArgument(2)
        );
    }

    public function testShouldSetDefaultDestinationIfNotSetInTag()
    {
        $container = new ContainerBuilder();
        $routerDef = $container->register('oro_message_queue.client.router')
            ->setArguments([null, null, null]);

        $container->register('processor_id')
            ->addTag('oro_message_queue.client.message_processor', ['topicName' => 'topic']);

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'topic' =>  [
                    ['processor_id', Config::DEFAULT_QUEUE_NAME]
                ]
            ],
            $routerDef->getArgument(2)
        );
    }

    public function testShouldBuildRouteFromSubscriberIfOnlyTopicNameSpecified()
    {
        $container = new ContainerBuilder();
        $routerDef = $container->register('oro_message_queue.client.router')
            ->setArguments([null, null, null]);

        $container->register('processor_id', OnlyTopicNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'topic-subscriber-name' =>  [
                    ['processor_id', Config::DEFAULT_QUEUE_NAME]
                ]
            ],
            $routerDef->getArgument(2)
        );
    }

    public function testShouldBuildRouteFromSubscriberIfProcessorNameSpecified()
    {
        $container = new ContainerBuilder();
        $routerDef = $container->register('oro_message_queue.client.router')
            ->setArguments([null, null, null]);

        $container->register('processor_id', ProcessorNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'topic-subscriber-name' =>  [
                    ['subscriber-processor-name', Config::DEFAULT_QUEUE_NAME]
                ]
            ],
            $routerDef->getArgument(2)
        );
    }

    public function testShouldBuildRouteFromSubscriberIfDestinationNameSpecified()
    {
        $container = new ContainerBuilder();
        $routerDef = $container->register('oro_message_queue.client.router')
            ->setArguments([null, null, null]);

        $container->register('processor_id', DestinationNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'topic-subscriber-name' =>  [
                    ['processor_id', 'subscriber-destination-name']
                ]
            ],
            $routerDef->getArgument(2)
        );
    }

    public function testShouldThrowExceptionWhenTopicSubscriberConfigurationIsInvalid()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic subscriber configuration is invalid. "[12345]"');

        $container = new ContainerBuilder();
        $container->register('oro_message_queue.client.router')
            ->setArguments(['', '']);

        $container->register('processor_id', InvalidTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);
    }
}
