<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;

class DestinationMetaRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetDestinationMetaWhenDestinationNotPresent(): void
    {
        $registry = new DestinationMetaRegistry(new Config('transport_prefix', 'default_queue'), []);

        self::assertEquals(
            new DestinationMeta('queue_name', 'transport_prefix.queue_name', []),
            $registry->getDestinationMeta('queue_name')
        );
    }

    public function testGetDestinationMetaWhenSubscribers(): void
    {
        $destinations = [
            'queue_name' => ['message_processor1'],
        ];

        $registry = new DestinationMetaRegistry(new Config('transport_prefix', 'default_queue'), $destinations);

        self::assertEquals(
            new DestinationMeta('queue_name', 'transport_prefix.queue_name', ['message_processor1']),
            $registry->getDestinationMeta('queue_name')
        );
    }

    public function testGetDestinationMetaByTransportQueueName(): void
    {
        $registry = new DestinationMetaRegistry(new Config('transport_prefix', 'default_queue'), []);

        self::assertEquals(
            new DestinationMeta('queue_name', 'transport_prefix.queue_name', []),
            $registry->getDestinationMetaByTransportQueueName('transport_prefix.queue_name')
        );
    }

    public function testShouldAllowGetAllDestinations(): void
    {
        $destinations = [
            'foo_queue_name' => [],
            'bar_queue_name' => [],
        ];

        $registry = new DestinationMetaRegistry(new Config('transport_prefix', 'default_queue'), $destinations);

        $destinations = $registry->getDestinationsMeta();
        self::assertInstanceOf(\Generator::class, $destinations);

        $destinations = iterator_to_array($destinations);

        self::assertContainsOnly(DestinationMeta::class, $destinations);
        self::assertCount(2, $destinations);

        self::assertSame('foo_queue_name', $destinations[0]->getQueueName());
        self::assertSame('transport_prefix.foo_queue_name', $destinations[0]->getTransportQueueName());

        self::assertSame('bar_queue_name', $destinations[1]->getQueueName());
        self::assertSame('transport_prefix.bar_queue_name', $destinations[1]->getTransportQueueName());
    }
}
