<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Async\MessageFilter;

use Oro\Bundle\ImapBundle\Async\MessageFilter\ClearInactiveMailboxMessageFilter;
use Oro\Bundle\ImapBundle\Async\Topic\ClearInactiveMailboxTopic;
use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use PHPUnit\Framework\TestCase;

class ClearInactiveMailboxMessageFilterTest extends TestCase
{
    public function testApplyForEmptyBuffer(): void
    {
        $filter = new ClearInactiveMailboxMessageFilter();

        $buffer = new MessageBuffer();
        $filter->apply($buffer);
        $this->assertEquals([], $buffer->getMessages());
    }

    public function testApply(): void
    {
        $filter = new ClearInactiveMailboxMessageFilter();
        $buffer = new MessageBuffer();

        // add same message twice
        $buffer->addMessage(ClearInactiveMailboxTopic::getName(), ['id' => 42]);
        $buffer->addMessage(ClearInactiveMailboxTopic::getName(), ['id' => 42]);

        $buffer->addMessage(ClearInactiveMailboxTopic::getName(), ['id' => 123]);

        // add same message twice
        $buffer->addMessage(ClearInactiveMailboxTopic::getName(), ['id' => 321]);
        $buffer->addMessage(ClearInactiveMailboxTopic::getName(), ['id' => 321]);

        // add same message twice (without ID)
        $buffer->addMessage(ClearInactiveMailboxTopic::getName(), []);
        $buffer->addMessage(ClearInactiveMailboxTopic::getName(), ['id' => null]);

        $filter->apply($buffer);

        $this->assertEquals(
            [
                0 => [ClearInactiveMailboxTopic::getName(), ['id' => 42]],
                2 => [ClearInactiveMailboxTopic::getName(), ['id' => 123]],
                3 => [ClearInactiveMailboxTopic::getName(), ['id' => 321]],
                5 => [ClearInactiveMailboxTopic::getName(), []]
            ],
            $buffer->getMessages()
        );
    }
}
