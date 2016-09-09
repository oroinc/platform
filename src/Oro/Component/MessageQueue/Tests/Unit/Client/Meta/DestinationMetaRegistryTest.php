<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;

class DestinationMetaRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithDestinations()
    {
        $destinations = [
            'aDestinationName' => [],
            'anotherDestinationName' => []
        ];

        $registry = new DestinationMetaRegistry(
            new Config('aPrefix', 'aRouterProcessorName', 'aRouterQueueName', 'aDefaultQueueName'),
            $destinations,
            'default'
        );

        $this->assertAttributeEquals($destinations, 'destinationsMeta', $registry);
    }

    public function testThrowIfThereIsNotMetaForRequestedClientDestinationName()
    {
        $registry = new DestinationMetaRegistry(
            new Config('aPrefix', 'aRouterProcessorName', 'aRouterQueueName', 'aDefaultQueueName'),
            [],
            'default'
        );

        $this->setExpectedException(
            \InvalidArgumentException::class,
            'The destination meta not found. Requested name `aName`'
        );
        $registry->getDestinationMeta('aName');
    }

    public function testShouldAllowGetDestinationByNameWithDefaultInfo()
    {
        $destinations = [
            'theDestinationName' => [],
        ];

        $registry = new DestinationMetaRegistry(
            new Config('aPrefix', 'aRouterProcessorName', 'aRouterQueueName', 'aDefaultQueueName'),
            $destinations,
            'theDestinationName'
        );

        $destination = $registry->getDestinationMeta('theDestinationName');
        $this->assertInstanceOf(DestinationMeta::class, $destination);
        $this->assertSame('theDestinationName', $destination->getClientName());
        $this->assertSame('aprefix.adefaultqueuename', $destination->getTransportName());
        $this->assertSame([], $destination->getSubscribers());
    }

    public function testShouldAllowGetDestinationByNameWithCustomInfo()
    {
        $destinations = [
            'theClientDestinationName' => ['transportName' => 'theTransportName', 'subscribers' => ['theSubscriber']],
        ];

        $registry = new DestinationMetaRegistry(
            new Config('aPrefix', 'aRouterProcessorName', 'aRouterQueueName', 'aDefaultQueueName'),
            $destinations,
            'default'
        );

        $destination = $registry->getDestinationMeta('theClientDestinationName');
        $this->assertInstanceOf(DestinationMeta::class, $destination);
        $this->assertSame('theClientDestinationName', $destination->getClientName());
        $this->assertSame('theTransportName', $destination->getTransportName());
        $this->assertSame(['theSubscriber'], $destination->getSubscribers());
    }

    public function testShouldAllowGetAllDestinations()
    {
        $destinations = [
            'fooDestinationName' => [],
            'barDestinationName' => [],
        ];

        $registry = new DestinationMetaRegistry(
            new Config('aPrefix', 'aRouterProcessorName', 'aRouterQueueName', 'aDefaultQueueName'),
            $destinations,
            'default'
        );

        $destinations = $registry->getDestinationsMeta();
        $this->assertInstanceOf(\Generator::class, $destinations);

        $destinations = iterator_to_array($destinations);
        /** @var DestinationMeta[] $destinations */

        $this->assertContainsOnly(DestinationMeta::class, $destinations);
        $this->assertCount(2, $destinations);

        $this->assertSame('fooDestinationName', $destinations[0]->getClientName());
        $this->assertSame('aprefix.foodestinationname', $destinations[0]->getTransportName());

        $this->assertSame('barDestinationName', $destinations[1]->getClientName());
        $this->assertSame('aprefix.bardestinationname', $destinations[1]->getTransportName());
    }
}
