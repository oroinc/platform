<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;

class DestinationMetaTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithExpectedArguments()
    {
        $destination = new DestinationMeta('aClientName', 'aTransportName');

        static::assertEquals('aClientName', $destination->getClientName());
        static::assertEquals('aTransportName', $destination->getTransportName());
        static::assertEquals([], $destination->getSubscribers());
    }

    public function testShouldAllowGetSubscribersSetInConstructor()
    {
        $destination = new DestinationMeta('aClientName', 'aTransportName', ['aSubscriber']);

        static::assertSame(['aSubscriber'], $destination->getSubscribers());
    }
}
