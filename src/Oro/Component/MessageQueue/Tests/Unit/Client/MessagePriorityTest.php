<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\MessagePriority;

class MessagePriorityTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldVeryLowPriorityHasExpectedValue(): void
    {
        self::assertSame('oro.message_queue.client.very_low_message_priority', MessagePriority::VERY_LOW);
    }

    public function testShouldLowPriorityHasExpectedValue(): void
    {
        self::assertSame('oro.message_queue.client.low_message_priority', MessagePriority::LOW);
    }

    public function testShouldMediumPriorityHasExpectedValue(): void
    {
        self::assertSame('oro.message_queue.client.normal_message_priority', MessagePriority::NORMAL);
    }

    public function testShouldHighPriorityHasExpectedValue(): void
    {
        self::assertSame('oro.message_queue.client.high_message_priority', MessagePriority::HIGH);
    }

    public function testShouldVeryHighPriorityHasExpectedValue(): void
    {
        self::assertSame('oro.message_queue.client.very_high_message_priority', MessagePriority::VERY_HIGH);
    }

    public function testGetUnknownMessagePriority(): void
    {
        $this->expectExceptionObject(
            new \InvalidArgumentException(
                'Given priority could not be converted to transport\'s one. Got: test'
            )
        );

        MessagePriority::getMessagePriority('test');
    }

    /**
     * @dataProvider getMessagePriorityDataProvider
     *
     * @param string $priority
     * @param int $transportPriority
     */
    public function testGetMessagePriority(string $priority, int $transportPriority): void
    {
        self::assertSame($transportPriority, MessagePriority::getMessagePriority($priority));
    }

    public function getMessagePriorityDataProvider(): array
    {
        return [
            [MessagePriority::VERY_LOW, 0],
            [MessagePriority::LOW, 1],
            [MessagePriority::NORMAL, 2],
            [MessagePriority::HIGH, 3],
            [MessagePriority::VERY_HIGH, 4],
        ];
    }

    public function testGetUnknownMessagePriorityName(): void
    {
        $this->expectExceptionObject(
            new \InvalidArgumentException(
                'Unknown priority test, expected one of Very Low, Low, Normal, High, Very High'
            )
        );

        MessagePriority::getMessagePriorityName('test');
    }

    /**
     * @dataProvider getMessagePriorityNameDataProvider
     *
     * @param string $priority
     * @param string $name
     */
    public function testGetMessagePriorityName(string $priority, string $name): void
    {
        self::assertSame($name, MessagePriority::getMessagePriorityName($priority));
    }

    public function getMessagePriorityNameDataProvider(): array
    {
        return [
            [MessagePriority::VERY_LOW, 'Very Low'],
            [MessagePriority::LOW, 'Low'],
            [MessagePriority::NORMAL, 'Normal'],
            [MessagePriority::HIGH, 'High'],
            [MessagePriority::VERY_HIGH, 'Very High'],
        ];
    }
}
