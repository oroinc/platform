<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\MessagePriority;

class MessagePriorityTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldVeryLowPriorityHasExpectedValue()
    {
        $this->assertSame('oro.message_queue.client.very_low_message_priority', MessagePriority::VERY_LOW);
    }

    public function testShouldLowPriorityHasExpectedValue()
    {
        $this->assertSame('oro.message_queue.client.low_message_priority', MessagePriority::LOW);
    }

    public function testShouldMediumPriorityHasExpectedValue()
    {
        $this->assertSame('oro.message_queue.client.normal_message_priority', MessagePriority::NORMAL);
    }

    public function testShouldHighPriorityHasExpectedValue()
    {
        $this->assertSame('oro.message_queue.client.high_message_priority', MessagePriority::HIGH);
    }

    public function testShouldVeryHighPriorityHasExpectedValue()
    {
        $this->assertSame('oro.message_queue.client.very_high_message_priority', MessagePriority::VERY_HIGH);
    }
}
