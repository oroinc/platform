<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildDestinationMetaRegistryPass;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\DestinationNameTopicSubscriber;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\OnlyTopicNameTopicSubscriber;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\ProcessorNameTopicSubscriber;
use Oro\Component\MessageQueue\Client\Config;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildDestinationMetaRegistryPassTest extends \PHPUnit\Framework\TestCase
{
    private BuildDestinationMetaRegistryPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new BuildDestinationMetaRegistryPass();
    }

    public function testShouldDoNothingIfRegistryServicesNotSetToContainer()
    {
        $container = new ContainerBuilder();

        $container->register('processor_id')
            ->addTag('oro_message_queue.client.message_processor', ['processorName' => 'processor']);

        $this->compiler->process($container);
    }

    public function testShouldBuildDestinationMetaRegistry()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.destination_meta_registry')
            ->setArguments([null, []]);

        $container->register('processor_id')
            ->addTag('oro_message_queue.client.message_processor', ['processorName' => 'processor']);

        $this->compiler->process($container);

        $this->assertEquals(
            [
                Config::DEFAULT_QUEUE_NAME =>  ['subscribers' => ['processor']]
            ],
            $registryDef->getArgument(1)
        );
    }

    public function testShouldSetServiceIdAdProcessorIdIfIsNotSetInTag()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.destination_meta_registry')
            ->setArguments([null, []]);

        $container->register('processor_id')
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                Config::DEFAULT_QUEUE_NAME =>  ['subscribers' => ['processor_id']]
            ],
            $registryDef->getArgument(1)
        );
    }

    public function testShouldSetDestinationTIfSetInTag()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.destination_meta_registry')
            ->setArguments([null, []]);

        $container->register('processor_id')
            ->addTag('oro_message_queue.client.message_processor', ['destinationName' => 'destination']);

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'destination' =>  ['subscribers' => ['processor_id']],
            ],
            $registryDef->getArgument(1)
        );
    }

    public function testShouldBuildDestinationFromSubscriberIfOnlyTopicNameSpecified()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.destination_meta_registry')
            ->setArguments([null, []]);

        $container->register('processor_id', OnlyTopicNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                Config::DEFAULT_QUEUE_NAME =>  ['subscribers' => ['processor_id']]
            ],
            $registryDef->getArgument(1)
        );
    }

    public function testShouldBuildDestinationFromSubscriberIfProcessorNameSpecified()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.destination_meta_registry')
            ->setArguments([null, []]);

        $container->register('processor_id', ProcessorNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                Config::DEFAULT_QUEUE_NAME =>  ['subscribers' => ['subscriber-processor-name']]
            ],
            $registryDef->getArgument(1)
        );
    }

    public function testShouldBuildDestinationFromSubscriberIfDestinationNameSpecified()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_message_queue.client.meta.destination_meta_registry')
            ->setArguments([null, []]);

        $container->register('processor_id', DestinationNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'subscriber-destination-name' => ['subscribers' => ['processor_id']],
            ],
            $registryDef->getArgument(1)
        );
    }

    public function testShouldMarkTaggedServicePublic()
    {
        $container = new ContainerBuilder();
        $container->register('oro_message_queue.client.meta.destination_meta_registry')
            ->setArguments([null, []]);

        $processorDef = $container->register('processor_id', DestinationNameTopicSubscriber::class)
            ->addTag('oro_message_queue.client.message_processor')
            ->setPublic(false);

        $this->compiler->process($container);

        $this->assertTrue($processorDef->isPublic());
    }
}
