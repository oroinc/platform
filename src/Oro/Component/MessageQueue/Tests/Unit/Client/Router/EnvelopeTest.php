<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\Router;

use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\Router\Envelope;
use Oro\Component\MessageQueue\Transport\Queue;

class EnvelopeTest extends \PHPUnit\Framework\TestCase
{
    public function testGetQueue(): void
    {
        $queue = new Queue('queue name');
        $message = new Message();
        $message->setBody('message body');

        $recipient = new Envelope($queue, $message);

        self::assertSame($message, $recipient->getMessage());
    }

    public function testGetMessage(): void
    {
        $queue = new Queue('queue name');
        $message = new Message();

        $recipient = new Envelope($queue, $message);

        self::assertSame($queue, $recipient->getQueue());
    }
}
