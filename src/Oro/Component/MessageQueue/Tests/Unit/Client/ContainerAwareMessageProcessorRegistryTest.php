<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Symfony\Component\DependencyInjection\Container;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Client\ContainerAwareMessageProcessorRegistry;

class ContainerAwareMessageProcessorRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldThrowExceptionIfProcessorIsNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('MessageProcessor was not found. processorName: "processor-name"');
        $registry = new ContainerAwareMessageProcessorRegistry([], new Container());
        $registry->get('processor-name');
    }

    public function testShouldThrowExceptionIfInstanceOfMessageProcessorIsInvalid()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid instance of message processor. expected: "%s", got: "%s"',
            MessageProcessorInterface::class,
            \stdClass::class
        ));
        $processor = new \stdClass();

        $container = new Container();
        $container->set('processor-id', $processor);

        $registry = new ContainerAwareMessageProcessorRegistry(['processor-name' => 'processor-id'], $container);

        $registry->get('processor-name');
    }

    public function testShouldReturnInstanceOfMessageProcessor()
    {
        $processor = $this->createMock(MessageProcessorInterface::class);

        $container = new Container();
        $container->set('processor-id', $processor);

        $registry = new ContainerAwareMessageProcessorRegistry(['processor-name' => 'processor-id'], $container);

        $this->assertSame($processor, $registry->get('processor-name'));
    }
}
