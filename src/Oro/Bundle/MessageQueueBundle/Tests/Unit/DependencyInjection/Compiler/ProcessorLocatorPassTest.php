<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\ProcessorLocatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProcessorLocatorPassTest extends \PHPUnit\Framework\TestCase
{
    private ProcessorLocatorPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ProcessorLocatorPass();
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $processorLocator = $container->register('oro_message_queue.processor_locator')
            ->addArgument([]);
        $container->register('processor_id')
            ->addTag('oro_message_queue.client.message_processor');

        $this->assertEquals([], $processorLocator->getArgument(0));

        $this->compiler->process($container);

        $this->assertEquals(
            [
                'oro_message_queue.client.delegate_message_processor' => new Reference(
                    'oro_message_queue.client.delegate_message_processor'
                ),
                'processor_id' => new Reference('processor_id')
            ],
            $processorLocator->getArgument(0)
        );
    }
}
