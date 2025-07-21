<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;
use PHPUnit\Framework\TestCase;

class DestinationMetaTest extends TestCase
{
    public function testCouldBeConstructedWithExpectedArguments(): void
    {
        $destination = new DestinationMeta('queue_name', 'transport_queue_name');

        self::assertEquals('queue_name', $destination->getQueueName());
        self::assertEquals('transport_queue_name', $destination->getTransportQueueName());
        self::assertEquals([], $destination->getMessageProcessors());
    }

    public function testShouldAllowGetSubscribersSetInConstructor(): void
    {
        $destination = new DestinationMeta('queue_name', 'transport_queue_name', ['message_processor1']);

        self::assertSame(['message_processor1'], $destination->getMessageProcessors());
    }
}
