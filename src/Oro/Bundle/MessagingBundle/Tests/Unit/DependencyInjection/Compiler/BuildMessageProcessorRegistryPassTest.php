<?php
namespace Oro\Bundle\MessagingBundle\Tests\DependencyInjection\Compiler;

use Oro\Bundle\MessagingBundle\DependencyInjection\Compiler\BuildMessageProcessorRegistryPass;
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
        $processor->addTag('oro_messaging.zero_config.message_processor', [
            'topicName' => 'topic',
            'processorName' => 'processor-name',
        ]);
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('oro_messaging.zero_config.message_processor_registry', $processorRegistry);

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
            'Topic name is not set but it is required. service: "processor", tag: "oro_messaging.zero_config.message'
        );

        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_messaging.zero_config.message_processor');
        $container->setDefinition('processor', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('oro_messaging.zero_config.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();
        $pass->process($container);
    }

    public function testShouldSetServiceIdAdProcessorIdIfIsNotSetInTag()
    {
        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_messaging.zero_config.message_processor', [
            'topicName' => 'topic',
        ]);
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('oro_messaging.zero_config.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();
        $pass->process($container);

        $expectedValue = [
            'processor-id' => 'processor-id',
        ];

        $this->assertEquals($expectedValue, $processorRegistry->getArgument(0));
    }
}
