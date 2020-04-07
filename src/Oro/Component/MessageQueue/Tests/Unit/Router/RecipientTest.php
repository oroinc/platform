<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Router;

use Oro\Component\MessageQueue\Router\Recipient;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\Queue;

class RecipientTest extends \PHPUnit\Framework\TestCase
{
    public function testGetQueue(): void
    {
        $queue = new Queue('queue name');
        $message = new Message();
        $message->setBody('message body');

        $recipient = new Recipient($queue, $message);

        $this->assertSame($message, $recipient->getMessage());
    }

    public function testGetMessage(): void
    {
        $queue = new Queue('queue name');
        $message = new Message();

        $recipient = new Recipient($queue, $message);

        $this->assertSame($queue, $recipient->getQueue());
    }
}
