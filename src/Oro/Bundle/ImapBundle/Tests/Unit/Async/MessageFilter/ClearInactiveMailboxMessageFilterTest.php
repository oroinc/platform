<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Async\MessageFilter;

use Oro\Bundle\ImapBundle\Async\MessageFilter\ClearInactiveMailboxMessageFilter;
use Oro\Bundle\ImapBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;

class ClearInactiveMailboxMessageFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testApplyForEmptyBuffer()
    {
        $filter = new ClearInactiveMailboxMessageFilter();

        $buffer = new MessageBuffer();
        $filter->apply($buffer);
        $this->assertEquals([], $buffer->getMessages());
    }

    public function testApply()
    {
        $filter = new ClearInactiveMailboxMessageFilter();
        $buffer = new MessageBuffer();

        // add same message twice
        $buffer->addMessage(Topics::CLEAR_INACTIVE_MAILBOX, ['id' => 42]);
        $buffer->addMessage(Topics::CLEAR_INACTIVE_MAILBOX, ['id' => 42]);

        $buffer->addMessage(Topics::CLEAR_INACTIVE_MAILBOX, ['id' => 123]);

        // add same message twice
        $buffer->addMessage(Topics::CLEAR_INACTIVE_MAILBOX, ['id' => 321]);
        $buffer->addMessage(Topics::CLEAR_INACTIVE_MAILBOX, ['id' => 321]);

        // add same message twice (without ID)
        $buffer->addMessage(Topics::CLEAR_INACTIVE_MAILBOX, []);
        $buffer->addMessage(Topics::CLEAR_INACTIVE_MAILBOX, ['id' => null]);

        $filter->apply($buffer);

        $this->assertEquals(
            [
                0 => [Topics::CLEAR_INACTIVE_MAILBOX, ['id' => 42]],
                2 => [Topics::CLEAR_INACTIVE_MAILBOX, ['id' => 123]],
                3 => [Topics::CLEAR_INACTIVE_MAILBOX, ['id' => 321]],
                5 => [Topics::CLEAR_INACTIVE_MAILBOX, []]
            ],
            $buffer->getMessages()
        );
    }
}
