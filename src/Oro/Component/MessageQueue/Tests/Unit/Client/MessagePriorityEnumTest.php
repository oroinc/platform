<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\MessagePriorityEnum;

class MessagePriorityEnumTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldLowPriorityBeEqualOne()
    {
        $this->assertSame(0, MessagePriorityEnum::LOW);
    }

    public function testShouldMediumPriorityBeEqualThree()
    {
        $this->assertSame(2, MessagePriorityEnum::MEDIUM);
    }

    public function testShouldHighPriorityBeEqualFive()
    {
        $this->assertSame(4, MessagePriorityEnum::HIGH);
    }
}