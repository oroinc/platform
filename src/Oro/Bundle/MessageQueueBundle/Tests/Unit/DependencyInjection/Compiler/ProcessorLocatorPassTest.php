<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\ProcessorLocatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ProcessorLocatorPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $processor = new Definition();
        $processor->addTag('oro_message_queue.client.message_processor');

        $processorLocator = new Definition(ServiceLocator::class, [[]]);

        $container = new ContainerBuilder();
        $container->setDefinition('processor-id', $processor);
        $container->setDefinition('oro_message_queue.processor_locator', $processorLocator);

        $this->assertEquals([], $processorLocator->getArgument(0));

        $pass = new ProcessorLocatorPass();
        $pass->process($container);

        $this->assertEquals(
            [
                'oro_message_queue.client.delegate_message_processor' => new Reference(
                    'oro_message_queue.client.delegate_message_processor'
                ),
                'processor-id' => new Reference('processor-id')
            ],
            $processorLocator->getArgument(0)
        );
    }
}
